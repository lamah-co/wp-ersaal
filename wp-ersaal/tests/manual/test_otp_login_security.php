<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../../wp-load.php';

use Ersaal\Core\Options;
use Ersaal\Modules\OTP\OTPLoginTwoFactor;
use Ersaal\Modules\OTP\OTPUserProfile;

echo "=== ERSAAL v1.1 OTP LOGIN SECURITY TESTS ===\n\n";
$failures = 0;
function loginOtpAssert(string $name, bool $condition, string $details = ''): void
{
    global $failures;
    echo str_pad($name, 42) . ($condition ? 'PASS' : 'FAIL' . ($details ? " ({$details})" : '')) . "\n";
    if (!$condition) { $failures++; }
}

global $wpdb;
$wpdb->query('START TRANSACTION');
$admins = get_users(['role' => 'administrator', 'number' => 1]);
$user = $admins[0] ?? null;
if (!$user instanceof WP_User) {
    echo "No administrator user available.\n";
    $wpdb->query('ROLLBACK');
    exit(1);
}
wp_set_current_user($user->ID);

update_option('ersaal_otp_enabled', true);
update_option('ersaal_otp_login_enabled', true);
update_user_meta($user->ID, OTPUserProfile::META_PHONE, '+218911234567');
update_user_meta($user->ID, OTPUserProfile::META_VERIFIED, 1);
update_user_meta($user->ID, OTPUserProfile::META_LOGIN_ENABLED, 1);

$service = ersaal_otp_service();
$login = new OTPLoginTwoFactor(new Options(), $service);
$required = new ReflectionMethod($login, 'isRequiredFor');
$required->setAccessible(true);
loginOtpAssert('three_way_enablement_required', $required->invoke($login, $user) === true);

$httpCalls = 0;
add_filter('pre_http_request', static function ($preempt, array $args, string $url) use (&$httpCalls) {
    if (strpos($url, '/api/otp/') !== false) { $httpCalls++; }
    return $preempt;
}, 10, 3);

$_SERVER['REQUEST_METHOD'] = 'GET';
unset($_POST['log'], $_POST['pwd']);
$blocked = $login->interceptAuthentication($user, $user->user_login, 'correct-password-not-retained');
loginOtpAssert('noninteractive_auth_fails_closed', is_wp_error($blocked) && $blocked->get_error_code() === 'ersaal_otp_required');
loginOtpAssert('noninteractive_does_not_send', $httpCalls === 0);

$invalid = new WP_Error('incorrect_password', 'Incorrect password');
$returned = $login->interceptAuthentication($invalid, $user->user_login, 'wrong');
loginOtpAssert('wrong_password_is_untouched', $returned === $invalid && $httpCalls === 0);

$profile = new OTPUserProfile(new Options(), $service);
$_POST['ersaal_otp_profile_save_nonce'] = wp_create_nonce('ersaal_otp_profile_save_' . $user->ID);
$_POST['ersaal_otp_phone'] = '+218922222222';
$_POST['ersaal_otp_login_2fa'] = '1';
$profile->save($user->ID);
loginOtpAssert('phone_change_clears_verification', !(bool) get_user_meta($user->ID, OTPUserProfile::META_VERIFIED, true));
loginOtpAssert('phone_change_disables_login_otp', !(bool) get_user_meta($user->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

$source = (string) file_get_contents((new ReflectionClass(OTPLoginTwoFactor::class))->getFileName());
$verifyPosition = strpos($source, '$result = $this->service->verify');
$cookiePosition = strpos($source, 'wp_set_auth_cookie');
loginOtpAssert('auth_cookie_only_after_verify', $verifyPosition !== false && $cookiePosition !== false && $cookiePosition > $verifyPosition);
loginOtpAssert('challenge_token_not_in_url', strpos($source, "add_query_arg('action', 'ersaal_otp'") !== false && strpos($source, "'challenge' =>") === false);
loginOtpAssert('challenge_cookie_is_hardened', strpos($source, "'httponly' => true") !== false && strpos($source, "'samesite' => 'Lax'") !== false);
loginOtpAssert('safe_redirect_enforced', strpos($source, 'wp_validate_redirect') !== false && strpos($source, 'wp_safe_redirect') !== false);
loginOtpAssert('local_failure_limit_present', strpos($source, 'MAX_FAILURES = 5') !== false);
loginOtpAssert('challenge_deleted_before_cookie', strpos($source, '$this->destroyChallenge($token);') < $cookiePosition);
loginOtpAssert('no_password_persistence', strpos($source, "'password'") === false && strpos($source, 'set_transient($password') === false);

$otpSources = '';
foreach (glob(dirname((new ReflectionClass(OTPLoginTwoFactor::class))->getFileName()) . '/*.php') ?: [] as $path) {
    $otpSources .= (string) file_get_contents($path);
}
loginOtpAssert('no_code_in_user_meta', !preg_match('/(?:update|add)_user_meta\s*\([^;]*(?:otp_)?code/i', $otpSources));
loginOtpAssert('no_code_in_transient', !preg_match('/set_transient\s*\([^;]*(?:otp_)?code/i', $otpSources));
loginOtpAssert('emergency_constant_supported', strpos($source, 'ERSAAL_DISABLE_LOGIN_OTP') !== false);

define('ERSAAL_DISABLE_LOGIN_OTP', true);
update_user_meta($user->ID, OTPUserProfile::META_PHONE, '+218911234567');
update_user_meta($user->ID, OTPUserProfile::META_VERIFIED, 1);
update_user_meta($user->ID, OTPUserProfile::META_LOGIN_ENABLED, 1);
loginOtpAssert('emergency_constant_bypasses_2fa', $required->invoke($login, $user) === false);

$wpdb->query('ROLLBACK');
echo "\n" . ($failures === 0 ? 'ALL OTP LOGIN SECURITY TESTS PASSED' : "{$failures} OTP LOGIN SECURITY TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
