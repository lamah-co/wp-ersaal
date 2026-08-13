<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';

echo "=== ERSAAL PHASE 7 WOOCOMMERCE TESTS ===\n\n";

if (!class_exists('WooCommerce')) {
    echo "woocommerce_missing              PASS (Skipped since WC is not installed)\n";
    exit;
}

$testFailures = 0;
function assertTestWc($name, $condition, $message = '') {
    global $testFailures;
    if ($condition) {
        echo str_pad($name, 32) . " PASS\n";
    } else {
        $testFailures++;
        echo str_pad($name, 32) . " FAIL" . ($message ? " ($message)" : "") . "\n";
    }
}

$optionNames = [
    'ersaal_wc_enable',
    'ersaal_wc_event_new_order_enable',
    'ersaal_wc_event_new_order_template',
    'ersaal_wc_event_processing_enable',
    'ersaal_wc_event_processing_template',
    'ersaal_wc_event_completed_enable',
    'ersaal_wc_event_completed_template',
    'ersaal_wc_event_cancelled_enable',
    'ersaal_wc_event_cancelled_template',
    'ersaal_wc_event_shipped_enable',
    'ersaal_wc_event_shipped_template',
    'ersaal_wc_admin_new_order_enable',
    'ersaal_wc_admin_phone',
    'ersaal_wc_admin_new_order_template',
];
$missingOption = new stdClass();
$optionBackups = [];
foreach ($optionNames as $optionName) {
    $optionBackups[$optionName] = get_option($optionName, $missingOption);
}

// Keep this regression test fully local. WooCommerce loads Action Scheduler,
// so short-circuit only Ersaal test jobs before they are persisted.
$queueFilter = static function ($pre, string $hook, array $args, string $group) {
    return $hook === 'ersaal_process_message_job' && $group === 'ersaal' ? 1 : $pre;
};
add_filter('pre_as_enqueue_async_action', $queueFilter, 10, 4);

// Ensure test options are set.
update_option('ersaal_wc_enable', true);
update_option('ersaal_wc_event_new_order_enable', true);
update_option('ersaal_wc_event_new_order_template', 'Order {order_number} received.');
foreach (['processing', 'completed', 'cancelled', 'shipped'] as $event) {
    update_option("ersaal_wc_event_{$event}_enable", true);
    update_option("ersaal_wc_event_{$event}_template", "Order {order_number} {$event}.");
}
update_option('ersaal_wc_admin_new_order_enable', true);
update_option('ersaal_wc_admin_phone', '+218922222222');
update_option('ersaal_wc_admin_new_order_template', 'New order {order_number}.');

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
assertTestWc('admin_order_notification', (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source_id = %s AND source_event = 'admin_new_order'", (string) $order_id)) === 1);

// 3. Duplicate event blocked
$initialCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = 'woocommerce' AND source_id = %s", (string)$order_id));
do_action('woocommerce_new_order', $order_id, $order);
$newCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source = 'woocommerce' AND source_id = %s", (string)$order_id));

assertTestWc('duplicate_event_blocked', $initialCount === $newCount);

// 4. Core status notifications.
do_action('woocommerce_order_status_processing', $order_id, $order);
do_action('woocommerce_order_status_completed', $order_id, $order);
do_action('woocommerce_order_status_cancelled', $order_id, $order);
foreach (['processing', 'completed', 'cancelled'] as $event) {
    assertTestWc($event . '_event', (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source_id = %s AND source_event = %s", (string) $order_id, $event)) === 1);
}

// 5. Dynamic custom status catch-all.
do_action('woocommerce_order_status_changed', $order_id, 'processing', 'shipped', $order);
assertTestWc('custom_status_event', (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE source_id = %s AND source_event = 'shipped'", (string) $order_id)) === 1);

// 6. Manual order SMS endpoint retains its nonce/capability and unique key.
$manualSource = (string) file_get_contents(ERSAAL_PLUGIN_DIR . 'src/Modules/WooCommerce/ManualOrderSmsBox.php');
assertTestWc('manual_order_sms_guards', strpos($manualSource, "check_ajax_referer('ersaal_manual_order_sms'") !== false && strpos($manualSource, "current_user_can('manage_woocommerce')") !== false);
assertTestWc('manual_order_sms_unique_key', strpos($manualSource, "'manual_woocommerce:' . \$orderId . ':' . wp_generate_uuid4()") !== false);

// Clean up
$order->delete(true);
$wpdb->delete($table, ['source' => 'woocommerce', 'source_id' => (string) $order_id], ['%s', '%s']);
remove_filter('pre_as_enqueue_async_action', $queueFilter, 10);
foreach ($optionBackups as $optionName => $value) {
    $value === $missingOption ? delete_option($optionName) : update_option($optionName, $value);
}

echo "\n" . ($testFailures === 0 ? 'All WooCommerce tests passed.' : "{$testFailures} WooCommerce test(s) failed.") . "\n";
exit($testFailures === 0 ? 0 : 1);
