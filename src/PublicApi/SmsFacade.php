<?php
declare(strict_types=1);

namespace Ersaal\PublicApi;

use Ersaal\Core\Options;
use Ersaal\Services\MessageService;
use Ersaal\Services\PhoneValidator;
use Ersaal\Storage\LogRepository;
use Ersaal\Support\Str;

final class SmsFacade
{
    private Options $options;
    private MessageService $messageService;
    private LogRepository $repository;
    private PhoneValidator $phoneValidator;

    public function __construct(
        Options $options,
        MessageService $messageService,
        LogRepository $repository,
        ?PhoneValidator $phoneValidator = null
    ) {
        $this->options = $options;
        $this->messageService = $messageService;
        $this->repository = $repository;
        $this->phoneValidator = $phoneValidator ?? new PhoneValidator();
    }

    public function isAvailable(): bool
    {
        $apiUrl = trim((string) $this->options->get('api_url', 'https://api.ersaal.com/'));
        $apiKey = trim((string) $this->options->get('api_key', ''));

        if ($apiKey === '' || filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = wp_parse_url($apiUrl, PHP_URL_SCHEME);
        $user = wp_parse_url($apiUrl, PHP_URL_USER);
        $pass = wp_parse_url($apiUrl, PHP_URL_PASS);

        return strtolower((string) $scheme) === 'https' && empty($user) && empty($pass);
    }

    public function send(array $request): SmsResult
    {
        $idempotencyKey = $this->resolveIdempotencyKey($request);
        if ($idempotencyKey === '') {
            return SmsResult::failure(
                'invalid_request',
                'invalid_idempotency_key',
                __('The SMS idempotency key is invalid.', 'ersaal')
            );
        }

        if (!$this->isAvailable()) {
            return SmsResult::failure(
                'unavailable',
                'missing_configuration',
                __('Ersaal SMS is not configured.', 'ersaal'),
                $idempotencyKey
            );
        }

        $message = sanitize_textarea_field((string) ($request['message'] ?? ''));
        if (trim($message) === '') {
            return SmsResult::failure(
                'invalid_request',
                'invalid_message',
                __('Message text is required.', 'ersaal'),
                $idempotencyKey
            );
        }

        $metadata = $this->sanitizeMetadata($request);
        $receiver = trim((string) ($request['receiver'] ?? ''));

        try {
            $receiver = $this->phoneValidator->normalize($receiver);
        } catch (\InvalidArgumentException $exception) {
            $logId = $this->logExpectedFailure(
                $idempotencyKey,
                $receiver,
                $message,
                $metadata,
                $exception->getMessage()
            );

            return SmsResult::failure(
                'failed',
                'invalid_phone',
                $exception->getMessage(),
                $idempotencyKey,
                $logId
            );
        }

        $defaults = $this->getDefaults($request);
        $payload = array_merge($metadata, [
            'receiver' => $receiver,
            'message' => $message,
            'sender' => $defaults['sender'],
            'payment_type' => $defaults['payment_type'],
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $this->messageService->send($payload);
            $log = $this->repository->getLogByKey($idempotencyKey);

            if (!$log) {
                return SmsResult::failure(
                    'error',
                    'log_not_created',
                    __('The SMS request could not be queued.', 'ersaal'),
                    $idempotencyKey
                );
            }

            return SmsResult::fromLog($log, $idempotencyKey);
        } catch (\Throwable $exception) {
            return SmsResult::failure(
                'error',
                'queue_failed',
                $this->sanitizeError($exception->getMessage()),
                $idempotencyKey
            );
        }
    }

    private function resolveIdempotencyKey(array $request): string
    {
        $key = isset($request['idempotency_key'])
            ? sanitize_text_field((string) $request['idempotency_key'])
            : wp_generate_uuid4();

        if ($key === '' || Str::length($key) > 100) {
            return '';
        }

        return $key;
    }

    private function sanitizeMetadata(array $request): array
    {
        $source = Str::substr(sanitize_key((string) ($request['source'] ?? 'api')), 0, 30);
        $recipientType = Str::substr(sanitize_key((string) ($request['recipient_type'] ?? 'customer')), 0, 30);

        return [
            'source' => $source !== '' ? $source : 'api',
            'source_id' => Str::substr(sanitize_text_field((string) ($request['source_id'] ?? '')), 0, 50),
            'source_event' => Str::substr(sanitize_key((string) ($request['source_event'] ?? '')), 0, 50),
            'recipient_type' => $recipientType !== '' ? $recipientType : 'customer',
        ];
    }

    private function getDefaults(array $request): array
    {
        $defaults = [
            'sender' => (string) $this->options->get(
                'sender',
                get_option('ersaal_wc_sender_id', 'Lamah')
            ),
            'payment_type' => (string) $this->options->get(
                'payment_type',
                get_option('ersaal_wc_payment_type', 'wallet')
            ),
        ];

        $defaults = apply_filters('ersaal_public_sms_defaults', $defaults, $request);
        $sender = sanitize_text_field((string) ($defaults['sender'] ?? 'Lamah'));
        $paymentType = sanitize_key((string) ($defaults['payment_type'] ?? 'wallet'));

        return [
            'sender' => $sender !== '' ? Str::substr($sender, 0, 50) : 'Lamah',
            'payment_type' => in_array($paymentType, ['wallet', 'subscription'], true)
                ? $paymentType
                : 'wallet',
        ];
    }

    private function logExpectedFailure(
        string $idempotencyKey,
        string $receiver,
        string $message,
        array $metadata,
        string $errorMessage
    ): ?int {
        try {
            $created = $this->repository->createProcessingLog(array_merge($metadata, [
                'idempotency_key' => $idempotencyKey,
                'phone_hash' => hash_hmac('sha256', $receiver, wp_salt('auth')),
                'phone_masked' => 'invalid',
                'sender' => '',
                'payment_type' => 'wallet',
                'message' => $message,
            ]));

            if ($created) {
                $this->repository->markFailed(
                    $idempotencyKey,
                    'failed',
                    null,
                    $this->sanitizeError($errorMessage)
                );
            }

            $log = $this->repository->getLogByKey($idempotencyKey);
            return $log ? (int) $log->id : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function sanitizeError(string $message): string
    {
        $message = sanitize_text_field($message);
        return Str::limit($message, 255);
    }
}
