<?php
declare(strict_types=1);
if (php_sapi_name() === 'cli' && !defined('ABSPATH')) {
    require_once dirname(__DIR__, 5) . '/wp-load.php';
}
defined('ABSPATH') || exit;

use Ersaal\Core\Options;
use Ersaal\Modules\OTP\OTPUserProfile;

echo "=== ERSAAL v1.1 OTP PROFILE HTTP END-TO-END TESTS ===\n\n";
$failures = 0;
function profileE2eAssert(string $name, bool $condition, string $details = ''): void
{
    global $failures;
    echo str_pad($name, 44) . ($condition ? 'PASS' : 'FAIL' . ($details !== '' ? " ({$details})" : '')) . "\n";
    if (!$condition) { $failures++; }
}

function profileE2ePost(string $cookie, string $nonce, string $action, int $userId, array $fields = []): array
{
    $handle = curl_init(admin_url('admin-ajax.php'));
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array_merge([
            'action' => $action,
            'nonce' => $nonce,
            'user_id' => $userId,
        ], $fields)),
        CURLOPT_COOKIE => AUTH_COOKIE . '=' . $cookie,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    return ['status' => $status, 'json' => json_decode($body, true)];
}

$admin = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
$customer = get_users(['role' => 'customer', 'number' => 1])[0] ?? null;
if (!$admin instanceof WP_User || !$customer instanceof WP_User) {
    echo "Administrator and customer fixtures are required.\n";
    exit(1);
}

$optionNames = ['ersaal_api_url', 'ersaal_api_key', 'ersaal_otp_enabled', 'ersaal_otp_login_enabled', 'ersaal_otp_sender', 'ersaal_otp_expiration'];
$metaNames = [OTPUserProfile::META_PHONE, OTPUserProfile::META_VERIFIED, OTPUserProfile::META_VERIFIED_AT, OTPUserProfile::META_LOGIN_ENABLED, 'billing_phone'];
$missing = new stdClass();
$optionBackups = [];
$metaBackups = [];
foreach ($optionNames as $name) { $optionBackups[$name] = get_option($name, $missing); }
foreach ($metaNames as $name) { $metaBackups[$name] = get_user_meta($customer->ID, $name, true); }

global $wpdb;
$otpLogFloor = (int) $wpdb->get_var("SELECT COALESCE(MAX(id), 0) FROM {$wpdb->prefix}ersaal_otp_logs");
$statePath = sys_get_temp_dir() . '/ersaal-otp-mock-state.json';
$expiration = time() + HOUR_IN_SECONDS;
$sessionManager = WP_Session_Tokens::get_instance($admin->ID);
$sessionToken = $sessionManager->create($expiration);
$authCookie = wp_generate_auth_cookie($admin->ID, $expiration, 'auth', $sessionToken);
$customerSessionManager = WP_Session_Tokens::get_instance($customer->ID);
$customerSessionToken = $customerSessionManager->create($expiration);
$customerAuthCookie = wp_generate_auth_cookie($customer->ID, $expiration, 'auth', $customerSessionToken);

try {
    file_put_contents($statePath, wp_json_encode(['mode' => 'ok', 'initiate' => 0, 'verify' => 0, 'used' => []]));
    update_option('ersaal_api_url', 'http://127.0.0.1:18080');
    update_option('ersaal_api_key', 'profile-e2e-placeholder');
    update_option('ersaal_otp_enabled', true);
    update_option('ersaal_otp_login_enabled', true);
    update_option('ersaal_otp_sender', 'Lamah');
    update_option('ersaal_otp_expiration', 1);
    update_user_meta($customer->ID, 'billing_phone', '+218912345678');
    foreach ([OTPUserProfile::META_PHONE, OTPUserProfile::META_VERIFIED, OTPUserProfile::META_VERIFIED_AT, OTPUserProfile::META_LOGIN_ENABLED] as $name) {
        delete_user_meta($customer->ID, $name);
    }

    $_COOKIE[AUTH_COOKIE] = $customerAuthCookie;
    wp_set_current_user($customer->ID);
    $customerNonce = wp_create_nonce('ersaal_otp_profile');
    $forbidden = profileE2ePost($customerAuthCookie, $customerNonce, 'ersaal_otp_profile_use_suggested', $admin->ID);
    profileE2eAssert('normal_user_cannot_target_other_user', $forbidden['status'] === 403 && empty($forbidden['json']['success']));

    $_COOKIE[AUTH_COOKIE] = $authCookie;
    wp_set_current_user($admin->ID);
    $nonce = wp_create_nonce('ersaal_otp_profile');

    $suggested = profileE2ePost($authCookie, $nonce, 'ersaal_otp_profile_use_suggested', $customer->ID);
    profileE2eAssert('suggested_phone_ajax_succeeds', $suggested['status'] === 200 && !empty($suggested['json']['success']));
    profileE2eAssert('suggestion_does_not_verify_or_save', get_user_meta($customer->ID, OTPUserProfile::META_PHONE, true) === '' && !(bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true));

    $send = profileE2ePost($authCookie, $nonce, 'ersaal_otp_profile_send', $customer->ID, ['phone' => '+218912345678']);
    $mock = json_decode((string) file_get_contents($statePath), true);
    profileE2eAssert('profile_send_uses_shared_otp_service', $send['status'] === 200 && !empty($send['json']['success']) && (int) ($mock['initiate'] ?? 0) === 1);

    $verify = profileE2ePost($authCookie, $nonce, 'ersaal_otp_profile_verify', $customer->ID, ['code' => '123456']);
    clean_user_cache($customer->ID);
    $verifyMessage = sanitize_text_field((string) ($verify['json']['data']['message'] ?? 'no message'));
    profileE2eAssert('correct_code_marks_phone_verified', $verify['status'] === 200 && !empty($verify['json']['success']) && (bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true), 'HTTP ' . $verify['status'] . ': ' . $verifyMessage);
    profileE2eAssert('verified_phone_is_saved_normalized', get_user_meta($customer->ID, OTPUserProfile::META_PHONE, true) === '00218912345678');

    $profile = new OTPUserProfile(new Options(), ersaal_otp_service());
    $_POST = [
        'ersaal_otp_profile_save_nonce' => wp_create_nonce('ersaal_otp_profile_save_' . $customer->ID),
        'ersaal_otp_phone' => '+218912345678',
        'ersaal_otp_login_2fa' => '1',
    ];
    $profile->save($customer->ID);
    profileE2eAssert('verified_user_can_enable_login_2fa', (bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

    $_POST['ersaal_otp_phone'] = '+218922222222';
    $profile->save($customer->ID);
    profileE2eAssert('changed_phone_resets_verified_and_2fa', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true) && !(bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

    $sendNew = profileE2ePost($authCookie, $nonce, 'ersaal_otp_profile_send', $customer->ID, ['phone' => '+218922222222']);
    $verifyNew = profileE2ePost($authCookie, $nonce, 'ersaal_otp_profile_verify', $customer->ID, ['code' => '123456']);
    clean_user_cache($customer->ID);
    $verifyNewMessage = sanitize_text_field((string) ($verifyNew['json']['data']['message'] ?? 'no message'));
    profileE2eAssert('new_phone_can_be_verified_again', !empty($sendNew['json']['success']) && !empty($verifyNew['json']['success']) && (bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true), 'HTTP ' . $verifyNew['status'] . ': ' . $verifyNewMessage);
    profileE2eAssert('new_verification_keeps_2fa_opt_in_off', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

    $contexts = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT context FROM {$wpdb->prefix}ersaal_otp_logs WHERE id > %d AND user_id = %d", $otpLogFloor, $customer->ID));
    profileE2eAssert('profile_activity_uses_safe_context', in_array('profile_verification', $contexts, true));
} finally {
    $_POST = [];
    unset($_COOKIE[AUTH_COOKIE]);
    $sessionManager->destroy($sessionToken);
    $customerSessionManager->destroy($customerSessionToken);
    foreach ($optionBackups as $name => $value) { $value === $missing ? delete_option($name) : update_option($name, $value); }
    foreach ($metaBackups as $name => $value) { $value === '' ? delete_user_meta($customer->ID, $name) : update_user_meta($customer->ID, $name, $value); }
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}ersaal_otp_logs WHERE id > %d AND user_id = %d", $otpLogFloor, $customer->ID));
    $transientPrefix = $wpdb->esc_like('_transient_ersaal_otp_enroll_' . $customer->ID . '_') . '%';
    $timeoutPrefix = $wpdb->esc_like('_transient_timeout_ersaal_otp_enroll_' . $customer->ID . '_') . '%';
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $transientPrefix, $timeoutPrefix));
    if (is_file($statePath)) { unlink($statePath); }
}

echo "\n" . ($failures === 0 ? 'ALL OTP PROFILE HTTP END-TO-END TESTS PASSED' : "{$failures} OTP PROFILE HTTP END-TO-END TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
