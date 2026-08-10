<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';

use Ersaal\Core\Database;
use Ersaal\Modules\OTP\OTPResult;

echo "=== ERSAAL v1.1 OTP CORE TESTS ===\n\n";

$failures = 0;
function otpAssert(string $name, bool $condition, string $details = ''): void
{
    global $failures;
    echo str_pad($name, 38) . ($condition ? "PASS" : "FAIL" . ($details ? " ({$details})" : '')) . "\n";
    if (!$condition) { $failures++; }
}

global $wpdb;
$smsTable = $wpdb->prefix . 'ersaal_logs';
$otpTable = $wpdb->prefix . 'ersaal_otp_logs';
$smsColumnsBefore = $wpdb->get_col("SHOW COLUMNS FROM {$smsTable}", 0);
update_option(Database::VERSION_KEY, '1.0.0');
(new Database())->upgrade();
$smsColumnsAfter = $wpdb->get_col("SHOW COLUMNS FROM {$smsTable}", 0);
$otpColumns = $wpdb->get_col("SHOW COLUMNS FROM {$otpTable}", 0);
otpAssert('migration_version', get_option(Database::VERSION_KEY) === Database::VERSION);
otpAssert('migration_preserves_sms_schema', $smsColumnsBefore === $smsColumnsAfter);
otpAssert('migration_creates_otp_table', in_array('phone_hash', $otpColumns, true) && in_array('phone_masked', $otpColumns, true) && !in_array('code', $otpColumns, true));

$wpdb->query('START TRANSACTION');
update_option('ersaal_api_url', 'https://otp.test');
update_option('ersaal_api_key', 'test_secret_token');
update_option('ersaal_otp_sender', 'Lamah');
update_option('ersaal_otp_payment_type', 'subscription');
update_option('ersaal_otp_length', 6);
update_option('ersaal_otp_expiration', 3);
update_option('ersaal_otp_language', 'ar');

$service = ersaal_otp_service();
otpAssert('shared_service_available', $service !== null);

$httpCalls = 0;
$captured = [];
$mock = [];
add_filter('pre_http_request', static function ($preempt, array $args, string $url) use (&$httpCalls, &$captured, &$mock) {
    if (strpos($url, '/api/otp/') === false) { return $preempt; }
    $httpCalls++;
    $captured = ['url' => $url, 'args' => $args, 'body' => json_decode((string) ($args['body'] ?? ''), true)];
    return $mock;
}, 10, 3);

update_option('ersaal_otp_enabled', false);
$beforeDisabled = $httpCalls;
$result = $service->initiate('+218911234567', 'test');
otpAssert('disabled_by_default_guard', !$result->isSuccess() && $result->getStatus() === 'disabled' && $httpCalls === $beforeDisabled);

update_option('ersaal_otp_enabled', true);
$beforeInvalid = $httpCalls;
$result = $service->initiate('not-a-phone', 'test');
otpAssert('invalid_phone_rejected_locally', !$result->isSuccess() && $result->getStatus() === 'invalid' && $httpCalls === $beforeInvalid);

$mock = ['response' => ['code' => 200, 'message' => 'OK'], 'headers' => [], 'body' => wp_json_encode(['request_id' => 'otp-ref-success', 'cost' => 0.12])];
$hookMetadata = [];
add_action('ersaal_otp_initiated', static function (array $resultData, string $context, array $metadata) use (&$hookMetadata): void {
    $hookMetadata = ['result' => $resultData, 'context' => $context, 'metadata' => $metadata];
}, 10, 3);
$result = $service->initiate('+218 91 123 4567', 'admin_test', ['user_id' => 1, 'phone' => '+218911234567', 'code' => '999999']);
otpAssert('initiate_success', $result->isSuccess() && $result->getStatus() === 'sent' && $result->getReference() === 'otp-ref-success');
otpAssert('initiate_contract_path', $captured['url'] === 'https://otp.test/api/otp/initiate');
otpAssert('initiate_contract_payload', $captured['body']['receiver'] === '+218911234567' && $captured['body']['sender'] === 'Lamah' && $captured['body']['length'] === 6 && $captured['body']['expiration'] === 3 && $captured['body']['lang'] === 'ar' && $captured['body']['payment_type'] === 'subscription');
otpAssert('initiate_auth_and_idempotency', $captured['args']['headers']['Authorization'] === 'Bearer test_secret_token' && !empty($captured['args']['headers']['Idempotency-Key']));
otpAssert('hooks_are_privacy_safe', ($hookMetadata['metadata']['phone_masked'] ?? '') !== '+218911234567' && !isset($hookMetadata['metadata']['phone'], $hookMetadata['metadata']['code']));

$log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$otpTable} WHERE reference = %s ORDER BY id DESC LIMIT 1", 'otp-ref-success'));
$serializedLog = wp_json_encode($log);
otpAssert('initiate_log_masks_phone', $log && $log->phone_masked !== '+218911234567' && strlen((string) $log->phone_hash) === 64);
otpAssert('initiate_log_has_no_secrets', strpos((string) $serializedLog, '+218911234567') === false && strpos((string) $serializedLog, '999999') === false && strpos((string) $serializedLog, 'test_secret_token') === false);

$mock = ['response' => ['code' => 200, 'message' => 'OK'], 'headers' => [], 'body' => wp_json_encode(['message' => 'OTP verified successfully'])];
$result = $service->verify('otp-ref-success', '123456', 'admin_test', ['user_id' => 1, 'phone' => '+218911234567']);
otpAssert('verify_success', $result->isSuccess() && $result->getStatus() === 'verified');
otpAssert('verify_contract_payload', $captured['url'] === 'https://otp.test/api/otp/verify' && $captured['body'] === ['request_id' => 'otp-ref-success', 'code' => '123456']);
$verifyLog = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$otpTable} WHERE reference = %s AND action = 'verify' ORDER BY id DESC LIMIT 1", 'otp-ref-success'));
otpAssert('verify_log_has_no_code', $verifyLog && strpos((string) wp_json_encode($verifyLog), '123456') === false);

$cases = [
    'wrong_code' => [400, ['message' => 'Invalid OTP code'], 'invalid', 'invalid_code', []],
    'expired_or_replay' => [404, ['message' => 'OTP expired or not found'], 'expired', 'expired', []],
    'active_code' => [400, ['message' => 'OTP already sent. Please wait.'], 'rate_limited', 'active_otp_exists', []],
    'rate_limit' => [429, ['message' => 'Too many requests'], 'rate_limited', 'rate_limited', ['retry-after' => '120']],
    'authentication' => [401, ['message' => 'Unauthenticated.'], 'failed', 'authentication_failed', []],
    'sender' => [422, ['message' => 'sender is invalid'], 'failed', 'sender_invalid', []],
    'balance' => [400, ['message' => 'Insufficient balance'], 'failed', 'subscription_or_balance', []],
    'server' => [500, ['message' => 'storage unavailable'], 'unavailable', 'service_unavailable', []],
];
foreach ($cases as $name => [$status, $body, $expectedStatus, $expectedCode, $headers]) {
    $mock = ['response' => ['code' => $status, 'message' => 'Mock'], 'headers' => $headers, 'body' => wp_json_encode($body)];
    $candidate = in_array($name, ['wrong_code', 'expired_or_replay'], true)
        ? $service->verify('otp-ref-case', '654321', 'custom', ['phone' => '+218911234567'])
        : $service->initiate('+218911234567', 'custom');
    otpAssert('maps_' . $name, !$candidate->isSuccess() && $candidate->getStatus() === $expectedStatus && $candidate->getErrorCode() === $expectedCode);
    if ($name === 'rate_limit') { otpAssert('maps_retry_after', $candidate->getRetryAfter() === 120); }
}

$mock = new WP_Error('http_request_failed', 'Connection refused');
$result = $service->initiate('+218911234567', 'custom');
otpAssert('maps_connection_failure', !$result->isSuccess() && $result->getStatus() === 'unavailable');

$stats = $service->getLogRepository()->getStatsToday();
otpAssert('otp_dashboard_stats', $stats['requests'] >= 1 && $stats['verified'] >= 1 && $stats['failed'] >= 1);

$wpdb->query('ROLLBACK');
echo "\n" . ($failures === 0 ? 'ALL OTP CORE TESTS PASSED' : "{$failures} OTP CORE TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
