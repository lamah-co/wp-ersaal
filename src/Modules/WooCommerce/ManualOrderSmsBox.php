<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

use Ersaal\Core\Options;
use Ersaal\Services\MessageService;

class ManualOrderSmsBox
{
    private Options $options;
    private MessageService $messageService;

    public function __construct(Options $options, MessageService $messageService)
    {
        $this->options = $options;
        $this->messageService = $messageService;
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetaBox'], 10, 2);
        add_action('wp_ajax_ersaal_manual_order_sms', [$this, 'ajaxSendSms']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        // Only load on WooCommerce order edit screens (classic and HPOS)
        $orderScreen = wc_get_page_screen_id('shop-order');
        $isOrderPage = ($hook === 'post.php' || $hook === 'post-new.php' || $hook === $orderScreen);
        if (!$isOrderPage) {
            return;
        }

        wp_enqueue_script(
            'ersaal-manual-order-sms',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/manual-order-sms.js',
            [],
            ERSAAL_VERSION,
            true
        );

        wp_localize_script('ersaal-manual-order-sms', 'ersaalOrderSms', [
            'i18n' => [
                'messageRequired'  => __('Message text is required.', 'ersaal-sms-gateway'),
                'unexpectedError'  => __('An unexpected error occurred.', 'ersaal-sms-gateway'),
            ],
        ]);
    }

    public function addMetaBox($post_type, $postOrOrder): void
    {
        $screen = wc_get_page_screen_id('shop-order');
        
        // For classic posts and HPOS
        if ($post_type === 'shop_order' || (is_string($screen) && $post_type === $screen)) {
            add_meta_box(
                'ersaal_manual_sms_box',
                __('Ersaal SMS', 'ersaal-sms-gateway'),
                [$this, 'renderMetaBox'],
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function renderMetaBox($postOrOrder): void
    {
        $order = ($postOrOrder instanceof \WC_Order) ? $postOrOrder : wc_get_order($postOrOrder->ID);
        if (!$order) {
            return;
        }

        $phone = $order->get_billing_phone();
        $sender = get_option('ersaal_wc_sender_id', 'Lamah');
        $payment = get_option('ersaal_wc_payment_type', 'wallet');
        
        wp_nonce_field('ersaal_manual_order_sms', 'ersaal_manual_order_sms_nonce');
        ?>
        <div class="ersaal-order-sms-container">
            <p>
                <strong><?php esc_html_e('Phone:', 'ersaal-sms-gateway'); ?></strong><br/>
                <input type="text" value="<?php echo esc_attr($phone); ?>" readonly class="widefat" />
            </p>
            <p>
                <strong><?php esc_html_e('Payment Type:', 'ersaal-sms-gateway'); ?></strong><br/>
                <select id="ersaal_order_payment_type" class="widefat">
                    <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal-sms-gateway'); ?></option>
                    <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal-sms-gateway'); ?></option>
                </select>
            </p>
            <p>
                <strong><?php esc_html_e('Message:', 'ersaal-sms-gateway'); ?></strong><br/>
                <textarea id="ersaal_order_message" rows="3" class="widefat"></textarea>
            </p>
            <p class="submit">
                <button type="button" id="ersaal_order_send_btn" class="button button-primary" data-order-id="<?php echo esc_attr((string)$order->get_id()); ?>">
                    <?php esc_html_e('Send SMS', 'ersaal-sms-gateway'); ?>
                </button>
                <span class="spinner" id="ersaal_order_spinner"></span>
            </p>
            <div id="ersaal_order_response" style="margin-top: 10px;"></div>
        </div>
        <?php
    }

    public function ajaxSendSms(): void
    {
        check_ajax_referer('ersaal_manual_order_sms', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ersaal-sms-gateway')], 403);
        }

        $orderId = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
        if ($orderId <= 0) {
            wp_send_json_error(['message' => __('Invalid order ID.', 'ersaal-sms-gateway')], 400);
        }

        // Object-level capability check: verify the caller can edit this specific order
        if (!current_user_can('edit_shop_order', $orderId) && !current_user_can('edit_post', $orderId)) {
            wp_send_json_error(['message' => __('You do not have permission to edit this order.', 'ersaal-sms-gateway')], 403);
        }

        $message = isset($_POST['message']) ? trim(sanitize_textarea_field(wp_unslash($_POST['message']))) : '';
        $rawPayment = isset($_POST['payment_type']) ? sanitize_key(wp_unslash($_POST['payment_type'])) : 'wallet';
        $payment = in_array($rawPayment, ['wallet', 'credit'], true) ? $rawPayment : 'wallet';

        if ($message === '') {
            wp_send_json_error(['message' => __('Message text is required.', 'ersaal-sms-gateway')], 400);
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found.', 'ersaal-sms-gateway')], 404);
        }

        $phone = (string) $order->get_billing_phone();
        if ($phone === '') {
            wp_send_json_error(['message' => __('Customer billing phone is missing.', 'ersaal-sms-gateway')], 400);
        }

        try {
            $phone = (new \Ersaal\Services\PhoneValidator())->normalize($phone);
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => __('Invalid customer phone number for SMS delivery.', 'ersaal-sms-gateway')], 400);
        }

        $sender = get_option('ersaal_wc_sender_id', 'Lamah');

        try {
            $idempotencyKey = 'manual_woocommerce:' . $orderId . ':' . wp_generate_uuid4();
            
            $this->messageService->send([
                'idempotency_key' => $idempotencyKey,
                'receiver' => $phone,
                'message' => $message,
                'sender' => $sender,
                'payment_type' => $payment,
                'source' => 'woocommerce',
                'source_id' => (string) $orderId,
                'source_event' => 'manual',
            ]);
            
            $order->add_order_note(__('Ersaal SMS Scheduled manually.', 'ersaal-sms-gateway'));

            wp_send_json_success(['message' => __('Message scheduled for sending.', 'ersaal-sms-gateway')]);
        } catch (\Throwable $e) {
            // Log full diagnostic detail to error log
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(sprintf('[Ersaal] Manual WooCommerce order SMS failed for order #%d: %s', $orderId, $e->getMessage()));
            
            $userMessage = __('Failed to schedule SMS message. Please check the Ersaal logs.', 'ersaal-sms-gateway');
            wp_send_json_error(['message' => $userMessage], 500);
        }
    }
}
