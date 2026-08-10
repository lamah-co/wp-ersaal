<?php
declare(strict_types=1);

define('WP_ADMIN', true);
require_once __DIR__ . '/../../../../../wp-load.php';

echo "=== ERSAAL v1.1 OTP UI & I18N TESTS ===\n\n";
$failures = 0;
function otpUiAssert(string $name, bool $condition): void
{
    global $failures;
    echo str_pad($name, 38) . ($condition ? 'PASS' : 'FAIL') . "\n";
    if (!$condition) { $failures++; }
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
wp_set_current_user((int) ($admins[0]->ID ?? 0));
do_action('admin_init');
$settings = get_registered_settings();
foreach (['ersaal_otp_enabled', 'ersaal_otp_login_enabled', 'ersaal_otp_sender', 'ersaal_otp_payment_type', 'ersaal_otp_length', 'ersaal_otp_expiration', 'ersaal_otp_language'] as $setting) {
    otpUiAssert('registered_' . $setting, isset($settings[$setting]));
}

$tabs = apply_filters('ersaal_settings_tabs', ['general' => 'General']);
otpUiAssert('settings_tab_registered', isset($tabs['otp']));

$pluginRoot = dirname(__DIR__, 2);
$settingsView = (string) file_get_contents($pluginRoot . '/admin/views/settings-otp.php');
$testView = (string) file_get_contents($pluginRoot . '/admin/views/otp-test.php');
$logsView = (string) file_get_contents($pluginRoot . '/admin/views/otp-logs.php');
$profileSource = (string) file_get_contents($pluginRoot . '/src/Modules/OTP/OTPUserProfile.php');
otpUiAssert('settings_has_global_opt_in', strpos($settingsView, 'ersaal_otp_enabled') !== false && strpos($settingsView, 'ersaal_otp_login_enabled') !== false);
otpUiAssert('test_has_state_machine', strpos($testView, 'data-otp-panel="send"') !== false && strpos($testView, 'data-otp-panel="verify"') !== false && strpos($testView, 'data-otp-panel="done"') !== false);
otpUiAssert('otp_logs_are_separate', strpos($logsView, 'ersaal-otp-logs') !== false && strpos($logsView, 'message_text') === false && strpos($logsView, 'parts_final') === false && strpos($logsView, 'cost_final') === false);
otpUiAssert('profile_has_explicit_opt_in', strpos($profileSource, 'ersaal_otp_login_2fa') !== false && strpos($profileSource, 'disabled(!$verified)') !== false);

$css = (string) file_get_contents($pluginRoot . '/admin/assets/css/admin.css') . (string) file_get_contents($pluginRoot . '/admin/assets/css/otp-login.css');
$js = (string) file_get_contents($pluginRoot . '/admin/assets/js/otp-admin.js') . (string) file_get_contents($pluginRoot . '/admin/assets/js/otp-profile.js');
otpUiAssert('no_physical_side_borders', !preg_match('/border-(?:left|right)\s*:/i', $css));
otpUiAssert('no_ui_framework_dependency', !preg_match('/\b(?:bootstrap|tailwind|bulma|foundation)\b/i', $css . $js));
otpUiAssert('rtl_uses_logical_properties', strpos($css, 'inline-size') !== false && strpos($css, 'margin-block') !== false);

otpUiAssert('arabic_new_string_loaded', __('OTP Test', 'ersaal') === 'اختبار رمز التحقق');
otpUiAssert('arabic_existing_string_preserved', __('Accepted', 'ersaal') === 'مقبولة');

$po = (string) file_get_contents($pluginRoot . '/languages/ersaal-ar.po');
$pot = (string) file_get_contents($pluginRoot . '/languages/ersaal.pot');
otpUiAssert('pot_contains_otp_strings', strpos($pot, 'msgid "OTP Test"') !== false && strpos($pot, 'msgid "Verify and sign in"') !== false);
otpUiAssert('po_contains_arabic_otp', strpos($po, 'msgstr "اختبار رمز التحقق"') !== false && strpos($po, 'msgstr "تحقق وسجّل الدخول"') !== false);

echo "\n" . ($failures === 0 ? 'ALL OTP UI & I18N TESTS PASSED' : "{$failures} OTP UI & I18N TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
