<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';

echo "=== ERSAAL PHASE 7 LOGS TESTS ===\n\n";

function assertTestLogs($name, $condition, $message = '') {
    if ($condition) {
        echo str_pad($name, 32) . " PASS\n";
    } else {
        echo str_pad($name, 32) . " FAIL" . ($message ? " ($message)" : "") . "\n";
    }
}

$repo = new \Ersaal\Storage\LogRepository();

// Insert dummy logs
global $wpdb;
$table = $repo->getTableName();
$wpdb->insert($table, [
    'idempotency_key' => 'test_log_1',
    'phone_hash' => 'hash',
    'phone_masked' => '+21891****111',
    'status' => 'accepted',
    'source' => 'manual',
    'message_id' => 'msg-1',
    'created_at' => current_time('mysql', true)
]);

$wpdb->insert($table, [
    'idempotency_key' => 'test_log_2',
    'phone_hash' => 'hash',
    'phone_masked' => '+21892****222',
    'status' => 'failed',
    'source' => 'woocommerce',
    'source_id' => '9999',
    'message_id' => null,
    'created_at' => current_time('mysql', true)
]);

// 1. Stats
$stats = $repo->getLogsStats();
assertTestLogs('log_stats', $stats['accepted'] >= 1 && $stats['failed'] >= 1);

// 2. Filter Status
$logs = $repo->getLogs(['status' => 'accepted']);
$allAccepted = true;
foreach ($logs['items'] as $item) {
    if ($item->status !== 'accepted') $allAccepted = false;
}
assertTestLogs('log_filter_status', $allAccepted && $logs['total'] > 0);

// 3. Filter Source
$logs = $repo->getLogs(['source' => 'woocommerce']);
$allWc = true;
foreach ($logs['items'] as $item) {
    if ($item->source !== 'woocommerce') $allWc = false;
}
assertTestLogs('log_filter_source', $allWc && $logs['total'] > 0);

// 4. Search Message ID
$logs = $repo->getLogs(['search' => 'msg-1']);
assertTestLogs('log_search_message_id', $logs['total'] >= 1 && $logs['items'][0]->message_id === 'msg-1');

// 5. Search Order ID
$logs = $repo->getLogs(['search' => '9999']);
assertTestLogs('woocommerce_reference', $logs['total'] >= 1 && $logs['items'][0]->source_id === '9999');

// 6. Pagination
$logs = $repo->getLogs(['per_page' => 1]);
assertTestLogs('log_pagination', count($logs['items']) === 1 && $logs['pages'] >= 2);

// Clean up
$wpdb->query("DELETE FROM {$table} WHERE idempotency_key IN ('test_log_1', 'test_log_2')");

echo "\nAll Logs tests finished.\n";
