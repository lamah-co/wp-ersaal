<?php
/**
 * Phase 6: Manual Send Tests
 *
 * Tests call ManualSendHandler::process() directly (returns array, no wp_die).
 * Nonce/Capability tests verified via source reflection.
 */
require_once __DIR__ . '/../../../../../wp-load.php';

// Several assertions intentionally verify the Arabic operator-facing errors.
// Keep the test independent from the site's current language.
$manualTestLocaleSwitched = switch_to_locale('ar');

use Ersaal\Modules\ManualSend\ManualSendHandler;
use Ersaal\Services\MessageService;
use Ersaal\Storage\LogRepository;

echo "=== ERSAAL PHASE 6 MANUAL SEND TESTS ===\n\n";

update_option('ersaal_api_url', 'http://localhost');
update_option('ersaal_api_key', 'test_secret_token');

$repository = new LogRepository();
$service    = new MessageService($repository);
$handler    = new ManualSendHandler($service);

global $wpdb;
$logsBefore = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs");

// ---------- HTTP mock infrastructure ----------
$mock_responses = [];
$current_test   = '';
$captured_body  = null; // spy on what gets sent to API

add_filter('pre_http_request', function ($false, $args, $url) use (&$mock_responses, &$current_test, &$captured_body) {
    // Only intercept sms/messages calls
    if (strpos($url, '/api/sms/messages') !== false) {
        $captured_body = json_decode($args['body'] ?? '{}', true);
    }
    if (isset($mock_responses[$current_test])) {
        return $mock_responses[$current_test];
    }
    return $false;
}, 10, 3);

function pad(string $name): void {
    echo str_pad($name, 35, ' ', STR_PAD_RIGHT);
}

$mock_success = [
    'response' => ['code' => 202, 'message' => 'Accepted'],
    'body'     => json_encode(['status' => 'pending', 'data' => ['id' => 'msg-uuid-123', 'parts' => 2, 'cost' => 0.10]]),
];

// ================================================================
// 1. valid_manual_send
// ================================================================
$current_test = 'valid_manual_send';
$mock_responses[$current_test] = $mock_success;
pad('valid_manual_send');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello', 'sender' => 'Lamah', 'payment_type' => 'wallet']);
echo ($r['success'] === true && isset($r['data']['log_id'])) ? "PASS\n" : "FAIL\n";

// ================================================================
// 2. invalid_nonce — guard exists in handleRequest
// ================================================================
pad('invalid_nonce');
$src = file_get_contents((new ReflectionClass(ManualSendHandler::class))->getFileName());
echo (strpos($src, 'check_ajax_referer') !== false) ? "PASS\n" : "FAIL\n";

// ================================================================
// 3. insufficient_capability — guard exists
// ================================================================
pad('insufficient_capability');
echo (strpos($src, "current_user_can('manage_options')") !== false) ? "PASS\n" : "FAIL\n";

// ================================================================
// 4. empty_phone
// ================================================================
$current_test = 'empty_phone';
$mock_responses[$current_test] = $mock_success;
pad('empty_phone');
$r = $handler->process(['phone' => '', 'message' => 'Hello']);
echo ($r['success'] === false && $r['status_code'] === 422) ? "PASS\n" : "FAIL\n";

// ================================================================
// 5. invalid_phone — letters only, stripped to empty
// ================================================================
$current_test = 'invalid_phone';
$mock_responses[$current_test] = $mock_success;
pad('invalid_phone');
$r = $handler->process(['phone' => 'abc xyz', 'message' => 'Hello']);
echo ($r['success'] === false && $r['status_code'] === 422) ? "PASS\n" : "FAIL\n";

// ================================================================
// 6. empty_message — empty string
// ================================================================
$current_test = 'empty_message_rejected';
$mock_responses[$current_test] = $mock_success;
$logsBefore6 = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs");
pad('empty_message_rejected');
$r = $handler->process(['phone' => '+218911234567', 'message' => '']);
$logsAfter6 = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs");
echo ($r['success'] === false && $r['status_code'] === 422 && mb_strpos($r['data']['message'], 'مطلوب') !== false && $logsAfter6 === $logsBefore6)
    ? "PASS\n" : "FAIL (success={$r['success']}, code={$r['status_code']}, logs_created=" . ($logsAfter6 - $logsBefore6) . ")\n";

// ================================================================
// 7. whitespace_message_rejected — spaces only
// ================================================================
$current_test = 'whitespace_message_rejected';
$mock_responses[$current_test] = $mock_success;
$logsBefore7 = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs");
pad('whitespace_message_rejected');
$r = $handler->process(['phone' => '+218911234567', 'message' => '     ']);
$logsAfter7 = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs");
echo ($r['success'] === false && $r['status_code'] === 422 && $logsAfter7 === $logsBefore7)
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 8. wallet_payment_propagation — verify API payload has payment_type=wallet
// ================================================================
$current_test = 'wallet_payment_propagation';
$mock_responses[$current_test] = $mock_success;
$captured_body = null;
pad('wallet_payment_propagation');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Test', 'sender' => 'X', 'payment_type' => 'wallet']);
echo ($captured_body !== null && ($captured_body['payment_type'] ?? '') === 'wallet') ? "PASS\n" : "FAIL (got: " . ($captured_body['payment_type'] ?? 'null') . ")\n";

// ================================================================
// 9. subscription_payment_propagation — verify API payload has payment_type=subscription
// ================================================================
$current_test = 'subscription_payment_propagation';
$mock_responses[$current_test] = $mock_success;
$captured_body = null;
pad('subscription_payment_propagation');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Test', 'sender' => 'X', 'payment_type' => 'subscription']);
echo ($captured_body !== null && ($captured_body['payment_type'] ?? '') === 'subscription') ? "PASS\n" : "FAIL (got: " . ($captured_body['payment_type'] ?? 'null') . ")\n";

// ================================================================
// 10. invalid_sender — API returns 422 with "sender"
// ================================================================
$current_test = 'invalid_sender';
$mock_responses[$current_test] = [
    'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
    'body'     => json_encode(['message' => 'The sender field is invalid.']),
];
pad('invalid_sender');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello', 'sender' => 'Bad']);
echo ($r['success'] === false && $r['data']['message'] === __('Sender ID is invalid or not approved for this project.', 'ersaal')) ? "PASS\n" : "FAIL ({$r['data']['message']})\n";

// ================================================================
// 11. authentication_failure — 401
// ================================================================
$current_test = 'authentication_failure';
$mock_responses[$current_test] = [
    'response' => ['code' => 401, 'message' => 'Unauthorized'],
    'body'     => json_encode(['message' => 'Unauthenticated.']),
];
pad('authentication_failure');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello']);
echo ($r['success'] === false && mb_strpos($r['data']['message'], 'المصادقة') !== false) ? "PASS\n" : "FAIL\n";

// ================================================================
// 12. wallet_zero_balance_message — 422 with "balance", payment=wallet
// ================================================================
$current_test = 'wallet_zero_balance_message';
$mock_responses[$current_test] = [
    'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
    'body'     => json_encode(['message' => 'Insufficient balance']),
];
pad('wallet_zero_balance_message');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello', 'payment_type' => 'wallet']);
$logW = isset($r['data']['log_id']) ? $wpdb->get_row($wpdb->prepare(
    "SELECT status FROM {$wpdb->prefix}ersaal_logs WHERE id = %d", $r['data']['log_id']
)) : null;
echo ($r['success'] === false
    && mb_strpos($r['data']['message'], 'المحفظة') !== false
    && $logW && $logW->status === 'failed')
    ? "PASS\n" : "FAIL (msg={$r['data']['message']}, status=" . ($logW->status ?? '?') . ")\n";

// ================================================================
// 13. subscription_error_message — 422 with "balance", payment=subscription
// ================================================================
$current_test = 'subscription_error_message';
$mock_responses[$current_test] = [
    'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
    'body'     => json_encode(['message' => 'Insufficient balance']),
];
pad('subscription_error_message');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello', 'payment_type' => 'subscription']);
echo ($r['success'] === false
    && mb_strpos($r['data']['message'], 'اشتراك') !== false
    && mb_strpos($r['data']['message'], 'المحفظة') === false)
    ? "PASS\n" : "FAIL ({$r['data']['message']})\n";

// ================================================================
// 14. message_id_mapping — message_id extracted from response
// ================================================================
$current_test = 'message_id_mapping';
$mock_responses[$current_test] = $mock_success;
pad('message_id_mapping');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Test ID', 'sender' => 'X']);
echo ($r['success'] === true && isset($r['data']['message_id']) && $r['data']['message_id'] === 'msg-uuid-123')
    ? "PASS\n" : "FAIL (message_id=" . ($r['data']['message_id'] ?? 'missing') . ")\n";

// ================================================================
// 15. missing_message_id_not_displayed — response without message_id
// ================================================================
$current_test = 'missing_message_id_not_displayed';
$mock_responses[$current_test] = [
    'response' => ['code' => 200, 'message' => 'OK'],
    'body'     => json_encode(['status' => 'ok']),
];
pad('missing_message_id_not_displayed');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Test no ID']);
echo ($r['success'] === true && !isset($r['data']['message_id']))
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 16. missing_cost_not_defaulted
// ================================================================
pad('missing_cost_not_defaulted');
echo ($r['success'] === true && !isset($r['data']['cost']))
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 17. missing_parts_not_defaulted
// ================================================================
pad('missing_parts_not_defaulted');
echo ($r['success'] === true && !isset($r['data']['parts']))
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 18. manual_rate_limit — 429, stored as error, no retry
// ================================================================
$current_test = 'manual_rate_limit';
$mock_responses[$current_test] = [
    'response' => ['code' => 429, 'message' => 'Too Many Requests'],
    'headers'  => ['retry-after' => '120'],
    'body'     => json_encode(['message' => 'Rate limit exceeded']),
];
pad('manual_rate_limit');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello']);
$logRL = isset($r['data']['log_id']) ? $wpdb->get_row($wpdb->prepare(
    "SELECT status, next_retry_at FROM {$wpdb->prefix}ersaal_logs WHERE id = %d", $r['data']['log_id']
)) : null;
echo ($r['success'] === false
    && mb_strpos($r['data']['message'], 'تجاوز') !== false
    && $logRL && $logRL->status === 'error'
    && $logRL->next_retry_at === null)
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 19. manual_server_error — 500, stored as error
// ================================================================
$current_test = 'manual_server_error';
$mock_responses[$current_test] = [
    'response' => ['code' => 500, 'message' => 'Internal Server Error'],
    'body'     => '',
];
pad('manual_server_error');
$r = $handler->process(['phone' => '+218911234567', 'message' => 'Hello']);
$logSE = isset($r['data']['log_id']) ? $wpdb->get_row($wpdb->prepare(
    "SELECT status FROM {$wpdb->prefix}ersaal_logs WHERE id = %d", $r['data']['log_id']
)) : null;
echo ($r['success'] === false
    && mb_strpos($r['data']['message'], 'تعذر إكمال الطلب') !== false
    && $logSE && $logSE->status === 'error')
    ? "PASS\n" : "FAIL\n";

// ================================================================
// 20. phone_not_stored
// ================================================================
pad('phone_not_stored');
$found = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs WHERE phone_masked LIKE '%218911234567%'");
echo ((int)$found === 0) ? "PASS\n" : "FAIL\n";

// ================================================================
// 21. api_key_not_logged
// ================================================================
pad('api_key_not_logged');
$found2 = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs WHERE api_error LIKE '%test_secret_token%'");
echo ((int)$found2 === 0) ? "PASS\n" : "FAIL\n";

echo "\nAll Manual Send tests finished.\n";
if ($manualTestLocaleSwitched) { restore_previous_locale(); }
