<?php
declare(strict_types=1);

namespace Ersaal\Modules\ManualSend;

use Ersaal\Services\MessageService;
use Ersaal\Jobs\MessageJob;
use Ersaal\Storage\LogRepository;
use Ersaal\API\Client;
use Ersaal\Core\Options;

class ManualSendHandler
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * WordPress AJAX entry point. Validates Nonce/Capability, sanitizes input,
     * delegates to process(), then outputs JSON and dies.
     */
    public function handleRequest(): void
    {
        if (!check_ajax_referer('ersaal_manual_send', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token.', 'ersaal')], 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient capabilities.', 'ersaal')], 403);
        }

        $input = [
            'phone'        => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            'sender'       => isset($_POST['sender']) ? sanitize_text_field(wp_unslash($_POST['sender'])) : '',
            'message'      => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'payment_type' => isset($_POST['payment_type']) ? sanitize_key(wp_unslash($_POST['payment_type'])) : '',
        ];

        $result = $this->process($input);

        if ($result['success']) {
            wp_send_json_success($result['data'], $result['status_code']);
        }

        wp_send_json_error($result['data'], $result['status_code']);
    }

    /**
     * Pure processing logic. No wp_send_json_*, no wp_die().
     * Returns a structured array that can be tested directly.
     */
    public function process(array $input): array
    {
        $phone       = trim((string) ($input['phone'] ?? ''));
        $sender      = trim((string) ($input['sender'] ?? ''));
        $message     = trim((string) ($input['message'] ?? ''));
        $paymentType = trim((string) ($input['payment_type'] ?? ''));

        // --- Validate required fields ---

        // Phone: strip non-numeric/+ then check
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if ($phone === '') {
            return [
                'success'     => false,
                'status_code' => 422,
                'data'        => ['message' => __('Invalid phone number.', 'ersaal')],
            ];
        }

        // Message must not be empty or whitespace-only
        if ($message === '') {
            return [
                'success'     => false,
                'status_code' => 422,
                'data'        => ['message' => __('نص الرسالة مطلوب.', 'ersaal')],
            ];
        }

        // Payment type must be one of the API-supported values
        if (!in_array($paymentType, ['wallet', 'subscription'], true)) {
            $paymentType = 'wallet';
        }

        $idempotencyKey = 'manual:0:' . wp_generate_uuid4();

        // --- Build payload with ONLY API-expected fields ---
        $apiPayload = [
            'receiver'     => $phone,
            'sender'       => $sender,
            'message'      => $message,
            'payment_type' => $paymentType,
        ];

        // Build the full service payload (includes source + idempotency for log creation)
        $servicePayload = array_merge($apiPayload, [
            'source'          => 'manual',
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            // 1. Create Log in "processing" state
            $returnedKey = $this->messageService->send($servicePayload);

            if ($returnedKey !== $idempotencyKey) {
                return [
                    'success'     => false,
                    'status_code' => 500,
                    'data'        => ['message' => __('Unexpected idempotency key conflict.', 'ersaal')],
                ];
            }

            // 2. Execute Job synchronously — pass only API fields + source
            $repository = new LogRepository();
            $client     = new Client(new Options());
            $job        = new MessageJob($repository, $client);

            $jobPayload = array_merge($apiPayload, [
                'source' => 'manual',
            ]);

            $job->handle($idempotencyKey, $jobPayload);

            // 3. Read final log status from DB
            $log = $repository->getLogByKey($idempotencyKey);

            if (!$log) {
                return [
                    'success'     => false,
                    'status_code' => 500,
                    'data'        => ['message' => __('Log not found after execution.', 'ersaal')],
                ];
            }

            // 4. Build response from final DB state
            return $this->buildResponseFromLog($log, $paymentType);

        } catch (\RuntimeException $e) {
            return [
                'success'     => false,
                'status_code' => 500,
                'data'        => ['message' => $e->getMessage()],
            ];
        } catch (\Throwable $e) {
            return [
                'success'     => false,
                'status_code' => 500,
                'data'        => ['message' => __('An unexpected internal error occurred.', 'ersaal')],
            ];
        }
    }

    /**
     * Reads the final log row and builds a structured result array.
     */
    private function buildResponseFromLog(object $log, string $paymentType): array
    {
        if ($log->status === 'accepted') {
            $data = [
                'message' => __('Message accepted by Ersaal platform.', 'ersaal'),
                'log_id'  => $log->id,
            ];

            // Only include message_id if the API actually returned one
            $mid = $log->message_id ?? null;
            if ($mid !== null && $mid !== '' && $mid !== 'unknown') {
                $data['message_id'] = $mid;
            }

            // Only include parts/cost if the API actually returned them (not zero defaults)
            $parts = $log->parts_final ?? null;
            if ($parts !== null && (int) $parts > 0) {
                $data['parts'] = (int) $parts;
            }

            $cost = $log->cost_final ?? null;
            if ($cost !== null && (float) $cost > 0) {
                $data['cost'] = (string) $cost;
            }

            return [
                'success'     => true,
                'status_code' => 200,
                'data'        => $data,
            ];
        }

        $msg = $this->getLocalizedErrorMessage(
            (string) $log->status,
            (int) ($log->api_http_code ?? 0),
            (string) ($log->api_error ?? ''),
            $paymentType
        );

        return [
            'success'     => false,
            'status_code' => $this->mapStatusToHttpCode($log),
            'data'        => [
                'message' => $msg,
                'log_id'  => $log->id,
            ],
        ];
    }

    /**
     * Maps the DB log to a sensible HTTP status code for the AJAX response.
     */
    private function mapStatusToHttpCode(object $log): int
    {
        $apiCode = (int) ($log->api_http_code ?? 0);
        if ($apiCode >= 400 && $apiCode < 600) {
            return $apiCode;
        }
        return 400;
    }

    /**
     * Translates the stored api_error / api_http_code into a user-facing Arabic message.
     * Uses payment_type context to distinguish wallet vs subscription errors.
     */
    private function getLocalizedErrorMessage(string $status, int $code, string $apiError, string $paymentType): string
    {
        // Balance / insufficient funds — context-aware
        if (stripos($apiError, 'balance') !== false || stripos($apiError, 'insufficient') !== false) {
            if ($paymentType === 'subscription') {
                return __('لا يوجد اشتراك صالح يسمح بإرسال هذه الرسالة.', 'ersaal');
            }
            return __('الرصيد غير كافٍ لإرسال الرسالة باستخدام المحفظة.', 'ersaal');
        }
        // Subscription-specific errors
        if (stripos($apiError, 'subscription') !== false) {
            return __('لا يوجد اشتراك صالح يسمح بإرسال هذه الرسالة.', 'ersaal');
        }
        // Sender ID
        if (stripos($apiError, 'sender') !== false) {
            return __('Sender ID غير صالح أو غير معتمد لهذا المشروع.', 'ersaal');
        }
        // Validation (400/422 generic)
        if ($code === 400 || $code === 422) {
            return __('يرجى مراجعة رقم الهاتف وبيانات الرسالة.', 'ersaal');
        }
        // Authentication
        if ($code === 401) {
            return __('تعذر المصادقة مع Ersaal. راجع API Key.', 'ersaal');
        }
        // IP / Forbidden
        if ($code === 403) {
            return __('عنوان IP الخاص بالخادم غير مسموح به في مشروع Ersaal.', 'ersaal');
        }
        // Rate limit
        if ($code === 429) {
            return __('تم تجاوز الحد المسموح للطلبات. حاول لاحقًا.', 'ersaal');
        }
        // Server / Connection / transient
        if ($code >= 500 || $status === 'error') {
            return __('تعذر إكمال الطلب مع منصة Ersaal.', 'ersaal');
        }

        return $apiError ?: __('حدث خطأ غير متوقع أثناء إرسال الرسالة.', 'ersaal');
    }
}
