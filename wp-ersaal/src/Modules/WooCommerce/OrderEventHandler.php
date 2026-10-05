<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

use Ersaal\Core\Options;
use Ersaal\Services\MessageService;
use Ersaal\Storage\LogRepository;

class OrderEventHandler
{
    private Options $options;
    private MessageService $messageService;
    private LogRepository $logRepo;

    public function __construct(Options $options, MessageService $messageService, ?LogRepository $logRepo = null)
    {
        $this->options = $options;
        $this->messageService = $messageService;
        $this->logRepo = $logRepo ?? new LogRepository();
    }

    public function register(): void
    {
        if (get_option('ersaal_wc_enable', false) == false) {
            return;
        }

        // New order hooks (support both checkout and admin/API creation)
        add_action('woocommerce_new_order', [$this, 'handleNewOrder'], 10, 2);

        // Status change hooks (Core specific)
        add_action('woocommerce_order_status_processing', [$this, 'handleProcessing'], 10, 2);
        add_action('woocommerce_order_status_completed', [$this, 'handleCompleted'], 10, 2);
        add_action('woocommerce_order_status_cancelled', [$this, 'handleCancelled'], 10, 2);
        
        // Catch-all for dynamic custom statuses
        add_action('woocommerce_order_status_changed', [$this, 'handleStatusChange'], 10, 4);
    }

    public function handleNewOrder($order_id, $order = null): void
    {
        $this->processEvent($order_id, 'new_order');
        $this->processAdminEvent($order_id, 'new_order');
    }

    private function processAdminEvent($order_id, string $event): void
    {
        if ($event !== 'new_order') {
            return;
        }
        
        if (get_option("ersaal_wc_admin_new_order_enable", false) == false) {
            return;
        }
        
        $adminPhone = get_option("ersaal_wc_admin_phone", '');
        try {
            $normalizedPhone = (new \Ersaal\Services\PhoneValidator())->normalize($adminPhone);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $template = get_option("ersaal_wc_admin_new_order_template", '');
        if (trim($template) === '') {
            return;
        }

        $message = TemplateEngine::render($template, $order);
        
        if (trim($message) === '') {
            return;
        }

        $sender = get_option('ersaal_wc_sender_id', 'Lamah');
        $paymentType = get_option('ersaal_wc_payment_type', 'wallet');

        $idempotencyKey = "woocommerce:{$order_id}:admin:{$event}";

        try {
            $this->messageService->send([
                'idempotency_key' => $idempotencyKey,
                'receiver' => $normalizedPhone,
                'message' => $message,
                'sender' => $sender,
                'payment_type' => $paymentType,
                'source' => 'woocommerce',
                'source_id' => (string) $order_id,
                'source_event' => 'admin_' . $event,
            ]);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Ersaal SMS Admin Notification Failed: ' . $e->getMessage());
            }
        }
    }

    public function handleProcessing($order_id, $order = null): void
    {
        $this->processEvent($order_id, 'processing');
    }

    public function handleCompleted($order_id, $order = null): void
    {
        $this->processEvent($order_id, 'completed');
    }

    public function handleCancelled($order_id, $order = null): void
    {
        $this->processEvent($order_id, 'cancelled');
    }

    public function handleStatusChange($order_id, $old_status, $new_status, $order): void
    {
        // Core statuses are already handled by explicit hooks to avoid breaking compatibility
        if (in_array($new_status, ['processing', 'completed', 'cancelled'], true)) {
            return;
        }
        
        $this->processEvent($order_id, $new_status);
    }

    private function processEvent($order_id, string $event): void
    {
        if (get_option("ersaal_wc_event_{$event}_enable", false) == false) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $phone = $order->get_billing_phone();
        try {
            $normalizedPhone = (new \Ersaal\Services\PhoneValidator())->normalize($phone);
        } catch (\InvalidArgumentException $e) {
            $this->logInvalidPhone($order_id, $event, $phone);
            return;
        }

        $template = get_option("ersaal_wc_event_{$event}_template", '');
        if (trim($template) === '') {
            return;
        }

        $message = TemplateEngine::render($template, $order);
        $message = apply_filters('ersaal_woocommerce_message', $message, $order_id, $event);
        
        if (trim($message) === '') {
            return;
        }

        $sender = get_option('ersaal_wc_sender_id', 'Lamah');
        $paymentType = get_option('ersaal_wc_payment_type', 'wallet');

        $idempotencyKey = "woocommerce:{$order_id}:{$event}";

        try {
            $this->messageService->send([
                'idempotency_key' => $idempotencyKey,
                'receiver' => $normalizedPhone,
                'message' => $message,
                'sender' => $sender,
                'payment_type' => $paymentType,
                'source' => 'woocommerce',
                'source_id' => (string) $order_id,
                'source_event' => $event,
            ]);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Ersaal SMS Customer Notification Failed: ' . $e->getMessage());
            }
        }
    }



    private function logInvalidPhone($order_id, string $event, string $originalPhone): void
    {
        // Prevent duplicate logs for invalid phone using idempotency
        $idempotencyKey = "woocommerce:{$order_id}:{$event}:invalid_phone";
        
        $logData = [
            'idempotency_key' => $idempotencyKey,
            'phone_hash'      => hash('sha256', $originalPhone),
            'phone_masked'    => 'invalid',
            'sender'          => get_option('ersaal_wc_sender_id', 'Lamah'),
            'payment_type'    => get_option('ersaal_wc_payment_type', 'wallet'),
            'source'          => 'woocommerce',
            'source_id'       => (string) $order_id,
            'source_event'    => $event,
            'recipient_type'  => 'customer',
            'message'         => '',
        ];

        $created = $this->logRepo->createProcessingLog($logData);
        if ($created) {
            $this->logRepo->markFailed(
                $idempotencyKey,
                'failed',
                null,
                __('Customer billing phone is missing or invalid.', 'ersaal')
            );
        }
    }
}
