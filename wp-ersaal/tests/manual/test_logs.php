<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';
require_once ERSAAL_PLUGIN_DIR . 'admin/LogsPage.php';
if (!function_exists('get_settings_errors')) {
    require_once ABSPATH . 'wp-admin/includes/template.php';
}

echo "=== ERSAAL PHASE 7 LOGS TESTS ===\n\n";

$testFailures = 0;
function assertTestLogs($name, $condition, $message = '') {
    global $testFailures;
    if ($condition) {
        echo str_pad($name, 32) . " PASS\n";
    } else {
        $testFailures++;
        echo str_pad($name, 32) . " FAIL" . ($message ? " ($message)" : "") . "\n";
    }
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
wp_set_current_user((int) ($admins[0]->ID ?? 0));
$_SERVER['REQUEST_METHOD'] = 'GET';

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

// 7. Message text saving and rendering tests
$wpdb->insert($table, [
    'idempotency_key' => 'test_log_3',
    'phone_hash' => 'hash',
    'phone_masked' => '+21891****333',
    'status' => 'accepted',
    'source' => 'manual',
    'message_text' => "Line 1\nLine 2",
    'message_excerpt' => "Line 1 Line 2",
    'parts_estimated' => null,
    'cost_estimated' => null,
    'created_at' => current_time('mysql', true)
]);

$wpdb->insert($table, [
    'idempotency_key' => 'test_log_4',
    'phone_hash' => 'hash',
    'phone_masked' => '+21891****444',
    'status' => 'accepted',
    'source' => 'manual',
    'message_text' => "<script>alert(1)</script>",
    'message_excerpt' => "<script>alert(1)</script>",
    'parts_estimated' => null,
    'cost_estimated' => null,
    'created_at' => current_time('mysql', true)
]);

// Verify saving in repository
$log3 = $repo->getLogByKey('test_log_3');
assertTestLogs('message_saved', $log3 !== null && strpos($log3->message_text, "Line 2") !== false);
assertTestLogs('message_multiline', $log3 !== null && strpos($log3->message_text, "\n") !== false);

// Capture HTML rendering
$_GET['page'] = 'ersaal-logs';
ob_start();
$page = new \Ersaal\Admin\LogsPage($repo);
$page->render();
$html = ob_get_clean();

assertTestLogs('message_rendered', strpos($html, 'Line 1 Line 2') !== false);
assertTestLogs('message_missing_shows_dash', strpos($html, '&mdash;') !== false); // for log 1 and 2
assertTestLogs('no_html_injection', strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false && strpos($html, '<script>alert(1)</script>') === false);
assertTestLogs('parts_missing_not_defaulted', strpos($html, 'parts_final&quot;:null') !== false);
assertTestLogs('cost_missing_not_defaulted', strpos($html, 'cost_final&quot;:null') !== false);

// 8. Test Delete Single
$wpdb->insert($table, [
    'idempotency_key' => 'test_log_delete',
    'phone_hash' => 'hash',
    'phone_masked' => '+21891****555',
    'status' => 'accepted',
    'source' => 'manual',
    'created_at' => current_time('mysql', true)
]);
$delete_id = $wpdb->insert_id;
$deleted = $repo->deleteById((int)$delete_id);
assertTestLogs('delete_single', $deleted === true && $repo->getLogByKey('test_log_delete') === null);

// 9. Test Invalid Delete
assertTestLogs('delete_invalid_id', $repo->deleteById(9999999) === false);

// 10. Test Bulk Delete
$wpdb->insert($table, ['idempotency_key' => 'test_log_b1', 'phone_hash' => 'hash', 'phone_masked' => '+218', 'status' => 'accepted', 'source' => 'manual', 'created_at' => current_time('mysql', true)]);
$b1 = $wpdb->insert_id;
$wpdb->insert($table, ['idempotency_key' => 'test_log_b2', 'phone_hash' => 'hash', 'phone_masked' => '+218', 'status' => 'accepted', 'source' => 'manual', 'created_at' => current_time('mysql', true)]);
$b2 = $wpdb->insert_id;
$bulkDeleted = $repo->deleteBulk([(int)$b1, (int)$b2]);
assertTestLogs('bulk_delete', $bulkDeleted === 2 && $repo->getLogByKey('test_log_b1') === null);
assertTestLogs('bulk_delete_empty', $repo->deleteBulk([]) === 0);

// 11. Test Date Filters
$wpdb->insert($table, ['idempotency_key' => 'test_date_1', 'phone_hash' => 'hash', 'phone_masked' => '+218', 'status' => 'accepted', 'source' => 'manual', 'created_at' => '2025-01-01 12:00:00']);
$wpdb->insert($table, ['idempotency_key' => 'test_date_2', 'phone_hash' => 'hash', 'phone_masked' => '+218', 'status' => 'accepted', 'source' => 'manual', 'created_at' => '2025-01-15 12:00:00']);

$logsFrom = $repo->getLogs(['date_from' => '2025-01-10', 'date_to' => '', 'per_page' => -1]);
$hasDate2 = false; $hasDate1 = false;
foreach ($logsFrom['items'] as $it) { if ($it->idempotency_key === 'test_date_1') $hasDate1 = true; if ($it->idempotency_key === 'test_date_2') $hasDate2 = true; }
assertTestLogs('date_filter_from', !$hasDate1 && $hasDate2);

$logsTo = $repo->getLogs(['date_from' => '', 'date_to' => '2025-01-10', 'per_page' => -1]);
$hasDate2 = false; $hasDate1 = false;
foreach ($logsTo['items'] as $it) { if ($it->idempotency_key === 'test_date_1') $hasDate1 = true; if ($it->idempotency_key === 'test_date_2') $hasDate2 = true; }
assertTestLogs('date_filter_to', $hasDate1 && !$hasDate2);

$logsRange = $repo->getLogs(['date_from' => '2025-01-01', 'date_to' => '2025-01-15', 'per_page' => -1]);
$hasDate2 = false; $hasDate1 = false;
foreach ($logsRange['items'] as $it) { if ($it->idempotency_key === 'test_date_1') $hasDate1 = true; if ($it->idempotency_key === 'test_date_2') $hasDate2 = true; }
assertTestLogs('date_filter_range', $hasDate1 && $hasDate2);

// 12. Test Event Filter
$wpdb->insert($table, ['idempotency_key' => 'test_event_1', 'phone_hash' => 'hash', 'phone_masked' => '+218', 'status' => 'accepted', 'source' => 'woocommerce', 'source_event' => 'completed', 'created_at' => current_time('mysql', true)]);
$logsEvent = $repo->getLogs(['event' => 'completed']);
$hasEvent = false;
foreach ($logsEvent['items'] as $it) { if ($it->idempotency_key === 'test_event_1') $hasEvent = true; }
assertTestLogs('event_filter', $hasEvent);

// Check distinct events
$distinct = $repo->getDistinctEvents();
assertTestLogs('distinct_events', in_array('completed', $distinct));

// Capture HTML for clear filters (ensuring button appears)
$_GET['status'] = 'accepted';
ob_start();
$page = new \Ersaal\Admin\LogsPage($repo);
$page->render();
$html2 = ob_get_clean();
assertTestLogs('clear_filters_url', strpos($html2, 'ersaal-btn-ghost ersaal-btn-sm') !== false);

// 13. CSV Export Check (simulated)
// The actual export ends script via `exit`, so we just test the log fetching part of CSV logic
$csvLogs = $repo->getLogs(['per_page' => -1, 'event' => 'completed']);
assertTestLogs('csv_respects_filters', count($csvLogs['items']) > 0);
$testLogCsv = $csvLogs['items'][0];
assertTestLogs('csv_no_sensitive_data', !isset($testLogCsv->api_key) && !isset($testLogCsv->authorization));

// 14. External source and reference extensions
$wpdb->insert($table, [
    'idempotency_key' => 'test_external_source',
    'phone_hash' => 'hash',
    'phone_masked' => '+21891 *** 1111',
    'status' => 'accepted',
    'source' => 'mis_booking',
    'source_id' => '125',
    'source_event' => 'booking_paid',
    'recipient_type' => 'customer',
    'created_at' => current_time('mysql', true),
]);
$externalLog = $repo->getLogByKey('test_external_source');

$sourceFilter = static function (array $sources): array {
    $sources['mis_booking'] = 'MIS Booking';
    return $sources;
};
$sourceLabelFilter = static function (string $label, string $source): string {
    return $source === 'mis_booking' ? 'MIS Booking' : $label;
};
$eventLabelFilter = static function (string $label, string $event): string {
    return $event === 'booking_paid' ? 'Booking paid' : $label;
};
$referenceLabelFilter = static function (string $label, object $log): string {
    return ($log->source ?? '') === 'mis_booking' ? 'Booking #' . $log->source_id : $label;
};
$referenceUrlFilter = static function (string $url, object $log): string {
    return ($log->source ?? '') === 'mis_booking'
        ? admin_url('admin.php?page=mis-bookings&action=view&id=' . absint($log->source_id))
        : $url;
};

add_filter('ersaal_log_sources', $sourceFilter);
add_filter('ersaal_log_source_label', $sourceLabelFilter, 99, 2);
add_filter('ersaal_log_event_label', $eventLabelFilter, 10, 2);
add_filter('ersaal_log_reference_label', $referenceLabelFilter, 10, 2);
add_filter('ersaal_log_reference_url', $referenceUrlFilter, 10, 2);

assertTestLogs('distinct_external_sources', in_array('mis_booking', $repo->getDistinctSources(), true));
assertTestLogs('external_source_label', ersaal_admin_source_label('mis_booking') === 'MIS Booking');
assertTestLogs('external_event_label', ersaal_admin_event_label('booking_paid') === 'Booking paid');
assertTestLogs('external_reference_label', $externalLog && ersaal_admin_log_reference_label($externalLog) === 'Booking #125');
assertTestLogs('external_reference_url', $externalLog && strpos(ersaal_admin_log_reference_url($externalLog), 'page=mis-bookings') !== false);

ob_start();
$page = new \Ersaal\Admin\LogsPage($repo);
$page->render();
$externalHtml = ob_get_clean();
assertTestLogs('external_source_filter_rendered', strpos($externalHtml, 'value="mis_booking"') !== false && strpos($externalHtml, 'MIS Booking') !== false);
assertTestLogs('external_reference_rendered', strpos($externalHtml, 'Booking #125') !== false && strpos($externalHtml, 'page=mis-bookings') !== false);

remove_filter('ersaal_log_sources', $sourceFilter);
remove_filter('ersaal_log_source_label', $sourceLabelFilter, 99);
remove_filter('ersaal_log_event_label', $eventLabelFilter, 10);
remove_filter('ersaal_log_reference_label', $referenceLabelFilter, 10);
remove_filter('ersaal_log_reference_url', $referenceUrlFilter, 10);

// Clean up
$wpdb->query("DELETE FROM {$table} WHERE idempotency_key IN ('test_log_1', 'test_log_2', 'test_log_3', 'test_log_4', 'test_date_1', 'test_date_2', 'test_event_1', 'test_external_source')");

echo "\n" . ($testFailures === 0 ? 'All Logs tests passed.' : "{$testFailures} Logs test(s) failed.") . "\n";
exit($testFailures === 0 ? 0 : 1);
