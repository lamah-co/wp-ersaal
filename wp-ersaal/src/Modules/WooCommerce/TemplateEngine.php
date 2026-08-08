<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

class TemplateEngine
{
    public static function render(string $template, \WC_Order $order): string
    {
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_name = trim($customer_name) !== '' ? trim($customer_name) : __('Customer', 'ersaal');
        
        $variables = [
            '{customer_name}' => $customer_name,
            '{order_number}' => $order->get_order_number(),
            '{order_total}' => html_entity_decode(strip_tags(wc_price($order->get_total(), ['currency' => $order->get_currency()]))),
            '{order_status}' => wc_get_order_status_name($order->get_status()),
            '{site_name}' => get_bloginfo('name'),
            '{billing_first_name}' => $order->get_billing_first_name(),
            '{billing_last_name}' => $order->get_billing_last_name(),
        ];
        
        return str_replace(array_keys($variables), array_values($variables), $template);
    }
}
