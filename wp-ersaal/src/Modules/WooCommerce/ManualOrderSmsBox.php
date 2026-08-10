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
    }

    public function addMetaBox($post_type, $postOrOrder): void
    {
        $screen = wc_get_page_screen_id('shop-order');
        
        // For classic posts and HPOS
        if ($post_type === 'shop_order' || (is_string($screen) && $post_type === $screen)) {
            add_meta_box(
                'ersaal_manual_sms_box',
                __('Ersaal SMS', 'ersaal'),
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
                <strong><?php esc_html_e('Phone:', 'ersaal'); ?></strong><br/>
                <input type="text" value="<?php echo esc_attr($phone); ?>" readonly class="widefat" />
            </p>
            <p>
                <strong><?php esc_html_e('Payment Type:', 'ersaal'); ?></strong><br/>
                <select id="ersaal_order_payment_type" class="widefat">
                    <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                    <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                </select>
            </p>
            <p>
                <strong><?php esc_html_e('Message:', 'ersaal'); ?></strong><br/>
                <textarea id="ersaal_order_message" rows="3" class="widefat"></textarea>
            </p>
            <p class="submit">
                <button type="button" id="ersaal_order_send_btn" class="button button-primary" data-order-id="<?php echo esc_attr((string)$order->get_id()); ?>">
                    <?php esc_html_e('Send SMS', 'ersaal'); ?>
                </button>
                <span class="spinner" id="ersaal_order_spinner"></span>
            </p>
            <div id="ersaal_order_response" style="margin-top: 10px;"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('ersaal_order_send_btn');
            if (!btn) return;
            
            btn.addEventListener('click', function() {
                const message = document.getElementById('ersaal_order_message').value.trim();
                const payment = document.getElementById('ersaal_order_payment_type').value;
                const nonce = document.getElementById('ersaal_manual_order_sms_nonce').value;
                const orderId = this.getAttribute('data-order-id');
                const responseDiv = document.getElementById('ersaal_order_response');
                const spinner = document.getElementById('ersaal_order_spinner');

                if (message === '') {
                    responseDiv.innerHTML = '<p style="color:red;"><?php esc_html_e('Message text is required.', 'ersaal'); ?></p>';
                    return;
                }

                btn.disabled = true;
                spinner.classList.add('is-active');
                responseDiv.innerHTML = '';

                const formData = new URLSearchParams();
                formData.append('action', 'ersaal_manual_order_sms');
                formData.append('nonce', nonce);
                formData.append('order_id', orderId);
                formData.append('message', message);
                formData.append('payment_type', payment);

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(res => res.json())
                .then(res => {
                    btn.disabled = false;
                    spinner.classList.remove('is-active');
                    if (res.success) {
                        responseDiv.innerHTML = '<p style="color:green;">' + res.data.message + '</p>';
                        document.getElementById('ersaal_order_message').value = '';
                    } else {
                        responseDiv.innerHTML = '<p style="color:red;">' + res.data.message + '</p>';
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    spinner.classList.remove('is-active');
                    responseDiv.innerHTML = '<p style="color:red;"><?php echo esc_js(__('An unexpected error occurred.', 'ersaal')); ?></p>';
                });
            });
        });
        </script>
        <?php
    }

    public function ajaxSendSms(): void
    {
        check_ajax_referer('ersaal_manual_order_sms', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ersaal')]);
        }

        $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $message = isset($_POST['message']) ? trim(sanitize_textarea_field($_POST['message'])) : '';
        $payment = isset($_POST['payment_type']) ? sanitize_text_field($_POST['payment_type']) : 'wallet';

        if ($message === '') {
            wp_send_json_error(['message' => __('Message text is required.', 'ersaal')]);
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found.', 'ersaal')]);
        }

        $phone = (string) $order->get_billing_phone();
        if ($phone === '') {
            wp_send_json_error(['message' => __('Customer billing phone is missing.', 'ersaal')]);
        }

        try {
            $phone = (new \Ersaal\Services\PhoneValidator())->normalize($phone);
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
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
            
            $order->add_order_note(__('Ersaal SMS Scheduled manually.', 'ersaal'));

            wp_send_json_success(['message' => __('Message scheduled for sending.', 'ersaal')]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
