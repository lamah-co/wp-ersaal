<?php
declare(strict_types=1);
if (php_sapi_name() === 'cli' && !defined('ABSPATH')) {
    require_once dirname(__DIR__, 5) . '/wp-load.php';
}
defined('ABSPATH') || exit;

use Ersaal\Core\Database;
use Ersaal\Core\Options;
use Ersaal\Modules\ManualSend\ManualSendHandler;
use Ersaal\Services\MessageService;
use Ersaal\Storage\LogRepository;

echo "=== ERSAAL v1.1 UPGRADE & DELETE-ALL REGRESSION ===\n\n";
$failures = 0;
function upgradeAssert(string $name, bool $condition): void
{
    global $failures;
    echo str_pad($name, 42) . ($condition ? 'PASS' : 'FAIL') . "\n";
    if (!$condition) { $failures++; }
}

global $wpdb;
$smsTable = $wpdb->prefix . 'ersaal_logs';
$otpTable = $wpdb->prefix . 'ersaal_otp_logs';
update_option(Database::VERSION_KEY, '1.0.0');
(new Database())->upgrade();
upgradeAssert('active_runtime_migration', get_option(Database::VERSION_KEY) === Database::VERSION && $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $otpTable)) === $otpTable);

$wpdb->query('START TRANSACTION');
update_option('ersaal_api_url', 'https://regression.test');
update_option('ersaal_api_key', 'local-placeholder');
update_option('ersaal_otp_enabled', true);
update_option('ersaal_otp_sender', 'Lamah');

$capturedPaths = [];
add_filter('pre_http_request', static function ($preempt, array $args, string $url) use (&$capturedPaths) {
    $capturedPaths[] = (string) parse_url($url, PHP_URL_PATH);
    if (strpos($url, '/api/sms/messages') !== false) {
        return ['response' => ['code' => 202, 'message' => 'Accepted'], 'headers' => [], 'body' => wp_json_encode(['data' => ['id' => 'regression-message', 'parts' => 1, 'cost' => 0.01]])];
    }
    if (strpos($url, '/api/otp/initiate') !== false) {
        return ['response' => ['code' => 200, 'message' => 'OK'], 'headers' => [], 'body' => wp_json_encode(['request_id' => 'regression-otp', 'cost' => 0.01])];
    }
    return $preempt;
}, 10, 3);

$wpdb->query("DELETE FROM {$smsTable}");
upgradeAssert('all_sms_logs_deleted_while_active', (int) $wpdb->get_var("SELECT COUNT(*) FROM {$smsTable}") === 0);
$handler = new ManualSendHandler(new MessageService(new LogRepository()));
$sent = $handler->process(['phone' => '+218911234567', 'message' => 'Regression', 'sender' => 'Lamah', 'payment_type' => 'wallet']);
upgradeAssert('sms_sends_after_delete_all', $sent['success'] === true);
upgradeAssert('sms_log_recreated_without_reactivation', (int) $wpdb->get_var("SELECT COUNT(*) FROM {$smsTable}") === 1);

$otp = ersaal_otp_service()->initiate('+218911234567', 'custom');
upgradeAssert('otp_works_after_runtime_migration', $otp->isSuccess() && $otp->getReference() === 'regression-otp');
upgradeAssert('sms_and_otp_use_expected_endpoints', in_array('/api/sms/messages', $capturedPaths, true) && in_array('/api/otp/initiate', $capturedPaths, true));

$wpdb->query('ROLLBACK');
echo "\n" . ($failures === 0 ? 'ALL UPGRADE REGRESSION TESTS PASSED' : "{$failures} UPGRADE REGRESSION TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
