<?php
declare(strict_types=1);
if (php_sapi_name() === 'cli' && !defined('ABSPATH')) {
    require_once dirname(__DIR__, 5) . '/wp-load.php';
}
defined('ABSPATH') || exit;

use Ersaal\API\Client;
use Ersaal\Core\Options;
use Ersaal\Jobs\MessageJob;
use Ersaal\PublicApi\SmsResult;
use Ersaal\Storage\LogRepository;

echo "=== ERSAAL PUBLIC SMS API TESTS ===\n\n";

$failures = 0;
function publicSmsAssert(string $name, bool $condition, string $details = ''): void
{
    global $failures;
    echo str_pad($name, 42) . ($condition ? 'PASS' : 'FAIL' . ($details !== '' ? " ({$details})" : '')) . "\n";
    if (!$condition) {
        $failures++;
    }
}

global $wpdb;
$wpdb->query('START TRANSACTION');

$repository = new LogRepository();
$prefix = 'public_api_test_' . wp_generate_password(8, false, false);
$httpCalls = 0;
$mockMode = 'accepted';
$acceptedHooks = 0;
$failedHooks = 0;

$findScheduledMessage = static function (string $idempotencyKey): ?array {
    foreach ((_get_cron_array() ?: []) as $timestamp => $hooks) {
        foreach (($hooks['ersaal_process_message_job'] ?? []) as $event) {
            $args = $event['args'] ?? [];
            if (isset($args[0]) && $args[0] === $idempotencyKey) {
                return [
                    'timestamp' => (int) $timestamp,
                    'args' => $args,
                ];
            }
        }
    }

    return null;
};

add_filter('pre_http_request', static function ($preempt, array $args, string $url) use (&$httpCalls, &$mockMode) {
    if (strpos($url, '/api/sms/messages') === false) {
        return $preempt;
    }

    $httpCalls++;
    if ($mockMode === 'failed') {
        return [
            'response' => ['code' => 422, 'message' => 'Unprocessable Entity'],
            'headers' => [],
            'body' => wp_json_encode(['message' => 'Invalid request']),
        ];
    }

    return [
        'response' => ['code' => 202, 'message' => 'Accepted'],
        'headers' => [],
        'body' => wp_json_encode(['data' => ['id' => 'public-message-id', 'parts' => 1, 'cost' => 0.01]]),
    ];
}, 10, 3);

add_action('ersaal_message_accepted', static function () use (&$acceptedHooks): void {
    $acceptedHooks++;
}, 10, 2);
add_action('ersaal_message_failed', static function () use (&$failedHooks): void {
    $failedHooks++;
}, 10, 3);

update_option('ersaal_api_url', 'https://public-api.test');
update_option('ersaal_api_key', 'local-placeholder');

publicSmsAssert('public_helpers_available', function_exists('ersaal_send_sms') && function_exists('ersaal_sms_available'));
publicSmsAssert('configured_api_is_available', ersaal_sms_available());

$key = $prefix . '_valid';
$request = [
    'receiver' => '+218 91 234 5678',
    'message' => 'External integration message',
    'idempotency_key' => $key,
    'source' => 'mis_booking',
    'source_id' => '125',
    'source_event' => 'booking_paid',
    'recipient_type' => 'customer',
];
$result = ersaal_send_sms($request);
$log = $repository->getLogByKey($key);

publicSmsAssert('public_send_returns_result', $result instanceof SmsResult);
publicSmsAssert('valid_phone_is_queued', $result->isSuccess() && $result->getStatus() === 'processing');
publicSmsAssert('result_contains_log_contract', $result->getLogId() === (int) ($log->id ?? 0) && $result->getMessageId() === null);
publicSmsAssert('phone_is_normalized_before_log', $log && hash_hmac('sha256', '00218912345678', wp_salt('auth')) === $log->phone_hash);
publicSmsAssert('external_metadata_is_logged', $log && $log->source === 'mis_booking' && $log->source_id === '125' && $log->source_event === 'booking_paid' && $log->recipient_type === 'customer');

$scheduled = $findScheduledMessage($key);
publicSmsAssert('cron_job_uses_positional_arguments', $scheduled && array_is_list($scheduled['args']) && count($scheduled['args']) === 2);
if ($scheduled) {
    wp_unschedule_event($scheduled['timestamp'], 'ersaal_process_message_job', $scheduled['args']);
}
publicSmsAssert('lost_cron_job_is_removed_for_test', $findScheduledMessage($key) === null);

$logsBeforeDuplicate = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs WHERE idempotency_key = %s",
    $key
));
$duplicate = ersaal_send_sms($request);
$logsAfterDuplicate = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_logs WHERE idempotency_key = %s",
    $key
));
publicSmsAssert('duplicate_key_reuses_log', $duplicate->isSuccess() && $duplicate->getLogId() === $result->getLogId() && $logsBeforeDuplicate === 1 && $logsAfterDuplicate === 1);

$recovered = $findScheduledMessage($key);
publicSmsAssert('untouched_processing_log_is_redispatched', $recovered !== null);
if ($recovered) {
    wp_unschedule_event($recovered['timestamp'], 'ersaal_process_message_job', $recovered['args']);
    do_action_ref_array('ersaal_process_message_job', $recovered['args']);
}
$deliveredLog = $repository->getLogByKey($key);
$callsAfterFirstWorker = $httpCalls;
if ($recovered) {
    do_action_ref_array('ersaal_process_message_job', $recovered['args']);
}
$afterDuplicateWorker = $repository->getLogByKey($key);
publicSmsAssert('cron_worker_starts_first_attempt', $deliveredLog && $deliveredLog->status === 'accepted' && (int) $deliveredLog->attempts === 1);
publicSmsAssert('duplicate_worker_does_not_resend', $httpCalls === $callsAfterFirstWorker && (int) ($afterDuplicateWorker->attempts ?? 0) === 1);

$callsBeforeInvalid = $httpCalls;
$invalidKey = $prefix . '_invalid';
$invalid = ersaal_send_sms(array_merge($request, [
    'receiver' => '0951234567',
    'idempotency_key' => $invalidKey,
]));
$invalidLog = $repository->getLogByKey($invalidKey);
publicSmsAssert('invalid_phone_returns_safe_error', !$invalid->isSuccess() && $invalid->getErrorCode() === 'invalid_phone');
publicSmsAssert('invalid_phone_is_privacy_logged', $invalidLog && $invalidLog->status === 'failed' && $invalidLog->phone_masked === 'invalid');
publicSmsAssert('invalid_phone_skips_http', $httpCalls === $callsBeforeInvalid);

delete_option('ersaal_api_key');
$missingConfig = ersaal_send_sms(array_merge($request, ['idempotency_key' => $prefix . '_missing_config']));
publicSmsAssert('missing_configuration_is_safe', !ersaal_sms_available() && !$missingConfig->isSuccess() && $missingConfig->getErrorCode() === 'missing_configuration');
update_option('ersaal_api_key', 'local-placeholder');

publicSmsAssert('success_fires_only_accepted_hook', $acceptedHooks === 1 && $failedHooks === 0);

$job = new MessageJob($repository, new Client(new Options()));
$failedKey = $prefix . '_delivery_failed';
$failedRequest = array_merge($request, ['idempotency_key' => $failedKey]);
$failedQueued = ersaal_send_sms($failedRequest);
$mockMode = 'failed';
$job->handle($failedKey, [
    'receiver' => '00218912345678',
    'message' => 'External integration message',
    'sender' => 'Lamah',
    'payment_type' => 'wallet',
    'source' => 'mis_booking',
    'source_id' => '125',
]);
$failedLog = $repository->getLogByKey($failedKey);
publicSmsAssert('failure_fires_failed_hook_once', $failedQueued->isSuccess() && $failedLog && $failedLog->status === 'failed' && $failedHooks === 1);

$sourceFilter = static function (array $sources): array {
    $sources['mis_booking'] = 'MIS Booking';
    return $sources;
};
$sourceLabelFilter = static function (string $label, string $source): string {
    return $source === 'mis_booking' ? 'MIS Booking' : $label;
};
$referenceLabelFilter = static function (string $label, object $candidate): string {
    return ($candidate->source ?? '') === 'mis_booking' ? 'Booking #' . $candidate->source_id : $label;
};
$referenceUrlFilter = static function (string $url, object $candidate): string {
    return ($candidate->source ?? '') === 'mis_booking' ? admin_url('admin.php?page=mis-bookings&id=' . $candidate->source_id) : $url;
};
add_filter('ersaal_log_sources', $sourceFilter);
add_filter('ersaal_log_source_label', $sourceLabelFilter, 99, 2);
add_filter('ersaal_log_reference_label', $referenceLabelFilter, 10, 2);
add_filter('ersaal_log_reference_url', $referenceUrlFilter, 10, 2);
publicSmsAssert('external_source_extension_works', ersaal_admin_source_label('mis_booking') === 'MIS Booking');
publicSmsAssert('generic_reference_extension_works', ersaal_admin_log_reference_label($log) === 'Booking #125' && strpos(ersaal_admin_log_reference_url($log), 'mis-bookings') !== false);
remove_filter('ersaal_log_sources', $sourceFilter);
remove_filter('ersaal_log_source_label', $sourceLabelFilter, 99);
remove_filter('ersaal_log_reference_label', $referenceLabelFilter, 10);
remove_filter('ersaal_log_reference_url', $referenceUrlFilter, 10);

$wpdb->query('ROLLBACK');

echo "\n" . ($failures === 0 ? 'ALL PUBLIC SMS API TESTS PASSED' : "{$failures} PUBLIC SMS API TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
