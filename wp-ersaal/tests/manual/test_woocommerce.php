<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';

echo "=== ERSAAL PHASE 7 WOOCOMMERCE TESTS ===\n\n";

if (!class_exists('WooCommerce')) {
    echo "woocommerce_missing              PASS (Skipped since WC is not installed)\n";
    exit;
}

function assertTestWc($name, $condition, $message = '') {
    if ($condition) {
        echo str_pad($name, 32) . " PASS\n";
    } else {
        echo str_pad($name, 32) . " FAIL" . ($message ? " ($message)" : "") . "\n";
    }
}

// Ensure option is set
update_option('ersaal_wc_enable', true);
update_option('ersaal_wc_event_new_order_enable', true);
update_option('ersaal_wc_event_new_order_template', 'Order {order_number} received.');

// Mock a lightweight WC Order if possible
$order = wc_create_order();
$order->set_billing_first_name('Test');
$order->set_billing_last_name('User');
$order->set_billing_phone('0912345678');
$order->save();

$order_id = $order->get_id();

// 1. Template Rendering
$template = 'Hello {customer_name}, Order {order_number}';
$rendered = \Ersaal\Modules\WooCommerce\TemplateEngine::render($template, $order);
assertTestWc('template_rendering', strpos($rendered, 'Test User') !== false && strpos($rendered, (string)$order->get_order_number()) !== false);

// 2. New Order Event
do_action('woocommerce_new_order', $order_id, $order);

// Check if log is created
global $wpdb;
$table = $wpdb->prefix . 'ersaal_logs';
$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE source = 'woocommerce' AND source_id = %s", (string)$order_id));

assertTestWc('new_order_event', $log !== null && $log->source_event === 'new_order');

// 3. Duplicate event blocked
$initialCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = 'woocommerce' AND source_id = %s", (string)$order_id));
do_action('woocommerce_new_order', $order_id, $order);
$newCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = 'woocommerce' AND source_id = %s", (string)$order_id));

assertTestWc('duplicate_event_blocked', $initialCount === $newCount);

// Clean up
$order->delete(true);
$wpdb->query("DELETE FROM {$table} WHERE source = 'woocommerce'");

echo "\nAll WooCommerce tests finished.\n";
