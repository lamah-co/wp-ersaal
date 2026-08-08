<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

class WooCommerceSettings
{
    public function register(): void
    {
        add_filter('ersaal_settings_tabs', [$this, 'addTab']);
        add_action('ersaal_render_settings_tab_woocommerce', [$this, 'renderTab']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addTab(array $tabs): array
    {
        $tabs['woocommerce'] = __('WooCommerce', 'ersaal');
        return $tabs;
    }

    public function registerSettings(): void
    {
        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_enable', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_sender_id', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Lamah'
        ]);
        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_payment_type', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'wallet'
        ]);
        
        $events = ['new_order', 'processing', 'completed', 'cancelled'];
        foreach ($events as $event) {
            register_setting('ersaal_woocommerce_settings', "ersaal_wc_event_{$event}_enable", [
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => false
            ]);
            register_setting('ersaal_woocommerce_settings', "ersaal_wc_event_{$event}_template", [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => $this->getDefaultTemplate($event)
            ]);
        }

        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_admin_new_order_enable', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);
        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_admin_phone', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        register_setting('ersaal_woocommerce_settings', 'ersaal_wc_admin_new_order_template', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => 'New order #{order_number} from {customer_name}. Total: {order_total}'
        ]);
    }

    private function getDefaultTemplate(string $event): string
    {
        switch ($event) {
            case 'new_order':
                return 'مرحباً {customer_name}، تم استلام طلبك رقم #{order_number}. شكراً لتعاملك مع {site_name}.';
            case 'processing':
                return 'طلبك رقم #{order_number} قيد التجهيز حالياً.';
            case 'completed':
                return 'تم إكمال طلبك رقم #{order_number}. شكراً لتعاملك مع {site_name}.';
            case 'cancelled':
                return 'تم إلغاء الطلب رقم #{order_number}.';
            default:
                return '';
        }
    }

    public function renderTab(): void
    {
        require ERSAAL_PLUGIN_DIR . 'admin/views/woocommerce-settings.php';
    }
}
