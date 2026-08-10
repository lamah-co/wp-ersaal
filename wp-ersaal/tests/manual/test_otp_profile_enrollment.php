<?php
declare(strict_types=1);

define('WP_ADMIN', true);
require_once __DIR__ . '/../../../../../wp-load.php';

use Ersaal\Core\Options;
use Ersaal\Modules\OTP\OTPUserProfile;

echo "=== ERSAAL v1.1 OTP PROFILE ENROLLMENT TESTS ===\n\n";
$failures = 0;
function profileOtpAssert(string $name, bool $condition): void
{
    global $failures;
    echo str_pad($name, 44) . ($condition ? 'PASS' : 'FAIL') . "\n";
    if (!$condition) { $failures++; }
}

function renderOtpProfile(OTPUserProfile $profile, WP_User $user): string
{
    ob_start();
    $profile->render($user);
    return (string) ob_get_clean();
}

global $wpdb;
$wpdb->query('START TRANSACTION');
$admin = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;
$customer = get_users(['role' => 'customer', 'number' => 1])[0] ?? null;
if (!$admin instanceof WP_User || !$customer instanceof WP_User) {
    echo "Administrator and customer fixtures are required.\n";
    $wpdb->query('ROLLBACK');
    exit(1);
}

$profile = new OTPUserProfile(new Options(), ersaal_otp_service());
profileOtpAssert('show_user_profile_hook_registered', has_action('show_user_profile') !== false);
profileOtpAssert('edit_user_profile_hook_registered', has_action('edit_user_profile') !== false);

foreach ([OTPUserProfile::META_PHONE, OTPUserProfile::META_VERIFIED, OTPUserProfile::META_VERIFIED_AT, OTPUserProfile::META_LOGIN_ENABLED, 'billing_phone'] as $key) {
    delete_user_meta($customer->ID, $key);
}
update_option('ersaal_otp_enabled', false);
update_option('ersaal_otp_login_enabled', false);

wp_set_current_user($admin->ID);
$adminOwn = renderOtpProfile($profile, $admin);
$adminOther = renderOtpProfile($profile, $customer);
profileOtpAssert('admin_own_profile_renders_section', strpos($adminOwn, 'ersaal-profile-otp') !== false && strpos($adminOwn, $admin->user_email) !== false);
profileOtpAssert('admin_other_profile_renders_section', strpos($adminOther, 'ersaal-profile-otp') !== false && strpos($adminOther, $customer->user_email) !== false);
profileOtpAssert('global_disabled_keeps_phone_visible', strpos($adminOther, 'id="ersaal_otp_phone"') !== false && strpos($adminOther, __('OTP service is currently disabled.', 'ersaal')) !== false);

wp_set_current_user($customer->ID);
$customerOwn = renderOtpProfile($profile, $customer);
$customerOther = renderOtpProfile($profile, $admin);
profileOtpAssert('normal_user_own_profile_renders', strpos($customerOwn, 'ersaal-profile-otp') !== false);
profileOtpAssert('normal_user_cannot_render_other', $customerOther === '');
profileOtpAssert('no_saved_phone_keeps_input_visible', strpos($customerOwn, 'id="ersaal_otp_phone"') !== false);

update_user_meta($customer->ID, 'billing_phone', '+218912345678');
$suggested = $profile->getSuggestedPhone($customer);
$suggestedHtml = renderOtpProfile($profile, $customer);
profileOtpAssert('billing_phone_detected', is_array($suggested) && $suggested['phone'] === '+218912345678');
profileOtpAssert('suggested_phone_is_masked_in_html', strpos($suggestedHtml, '+21891 *** 5678') !== false && strpos($suggestedHtml, '+218912345678') === false);
profileOtpAssert('suggested_phone_is_not_verified', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true));

update_user_meta($customer->ID, OTPUserProfile::META_PHONE, '+218912345678');
profileOtpAssert('saved_otp_phone_has_priority', $profile->getSuggestedPhone($customer) === null);

update_option('ersaal_otp_enabled', true);
update_option('ersaal_otp_login_enabled', true);
update_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, 0);
$_POST = [
    'ersaal_otp_profile_save_nonce' => wp_create_nonce('ersaal_otp_profile_save_' . $customer->ID),
    'ersaal_otp_phone' => '+218912345678',
    'ersaal_otp_login_2fa' => '1',
];
$profile->save($customer->ID);
profileOtpAssert('unverified_phone_cannot_enable_2fa', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

update_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, 1);
update_option('ersaal_otp_login_enabled', false);
$profile->save($customer->ID);
profileOtpAssert('global_setting_blocks_user_2fa', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

update_option('ersaal_otp_login_enabled', true);
$profile->save($customer->ID);
profileOtpAssert('verified_phone_can_enable_2fa', (bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

$_POST['ersaal_otp_phone'] = '+218922222222';
$profile->save($customer->ID);
profileOtpAssert('phone_change_clears_verification', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_VERIFIED, true));
profileOtpAssert('phone_change_disables_2fa', !(bool) get_user_meta($customer->ID, OTPUserProfile::META_LOGIN_ENABLED, true));

$_POST = [];
$wpdb->query('ROLLBACK');
echo "\n" . ($failures === 0 ? 'ALL OTP PROFILE ENROLLMENT TESTS PASSED' : "{$failures} OTP PROFILE ENROLLMENT TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
