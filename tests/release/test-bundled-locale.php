<?php
declare(strict_types=1);

// Require this file through wp eval with WP_ADMIN defined on an isolated installation.
defined('ABSPATH') || exit;

$checks = 0;
$assert = static function (string $name, bool $condition) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($name);
    }
    $checks++;
    echo "PASS: {$name}\n";
};
$domain = 'ersaal-sms-gateway';
$assert('release version', ERSAAL_VERSION === '1.3.2');
$assert('no external Arabic plugin catalog', !file_exists(WP_LANG_DIR . '/plugins/' . $domain . '-ar.mo') && !file_exists(WP_LANG_DIR . '/plugins/' . $domain . '-ar.l10n.php'));

$loaded = [];
add_filter('load_textdomain_mofile', static function (string $file, string $textdomain) use (&$loaded, $domain): string {
    if ($textdomain === $domain) {
        $loaded[] = $file;
    }
    return $file;
}, 10, 2);
// Translation and view tests must never send messages or call the live API.
add_filter('pre_http_request', static function () {
    return new WP_Error('release_test', 'External HTTP disabled during release tests.');
});

$assert('Arabic locale switch', switch_to_locale('ar'));
$assert('Arabic RTL', is_rtl());
$assert('bundled settings translation', __('Settings', $domain) === 'الإعدادات');
$assert('new API error translation', __('Ersaal API requires a secure HTTPS connection.', $domain) === 'تتطلب واجهة إرسال البرمجية اتصال HTTPS آمناً.');
$assert('reviewed user ID translation', __('User ID', $domain) === 'معرّف المستخدم');
foreach ([0, 1, 2, 3, 11, 100] as $count) {
    $translated = _n('Showing %s matching record', 'Showing %s matching records', $count, $domain);
    $assert('Arabic plural ' . $count, strpos($translated, 'Showing') === false && strpos($translated, '%s') !== false);
}
$assert('catalog loaded from plugin languages directory', in_array(dirname(ERSAAL_PLUGIN_FILE) . '/languages/' . $domain . '-ar.mo', $loaded, true));

$admins = get_users(['role' => 'administrator', 'number' => 1]);
wp_set_current_user((int) $admins[0]->ID);
$plugin = ersaal_plugin();
foreach (['toplevel_page_ersaal-dashboard', 'ersaal_page_ersaal-settings', 'ersaal_page_ersaal-logs'] as $hook) {
    wp_dequeue_style('ersaal-admin');
    wp_dequeue_style('ersaal-tokens');
    $plugin->enqueueGlobalAdminAssets($hook);
    $assert('shared styles on ' . $hook, wp_style_is('ersaal-admin', 'enqueued') && wp_style_is('ersaal-tokens', 'enqueued'));
}
$pluginRoot = dirname(ERSAAL_PLUGIN_FILE);
foreach (['admin/assets/css/ersaal-tokens.css', 'admin/assets/css/admin.css', 'admin/assets/js/manual-send.js'] as $file) {
    $assert('installed runtime asset ' . $file, is_readable($pluginRoot . '/' . $file));
}
$options = new \Ersaal\Core\Options();
$repository = new \Ersaal\Storage\LogRepository();
$otpRepository = new \Ersaal\Modules\OTP\OTPLogRepository();
foreach (['DashboardPage', 'SettingsPage', 'ManualSendPage', 'LogsPage', 'HelpPage'] as $class) {
    require_once $pluginRoot . '/admin/' . $class . '.php';
}
$pages = [
    'Dashboard' => new \Ersaal\Admin\DashboardPage($repository, $otpRepository, $options),
    'Settings' => new \Ersaal\Admin\SettingsPage($options),
    'Send SMS' => new \Ersaal\Admin\ManualSendPage($options),
    'Logs' => new \Ersaal\Admin\LogsPage($repository),
];
foreach ($pages as $title => $page) {
    ob_start();
    $page->render();
    $html = ob_get_clean();
    $assert('Arabic page ' . $title, strpos($html, esc_html__($title, $domain)) !== false && strpos($html, 'ersaal-page-title') !== false);
}
$help = new \Ersaal\Admin\HelpPage();
ob_start();
$help->render();
$html = ob_get_clean();
$assert('Arabic help view', strpos($html, 'المساعدة ودليل الاستخدام') !== false && strpos($html, 'dir="rtl"') !== false);
$otpAdmin = new \Ersaal\Modules\OTP\OTPAdminController($options, ersaal_otp_service());
foreach (['renderSettingsTab', 'renderTestPage', 'renderLogsPage'] as $method) {
    ob_start();
    $otpAdmin->$method();
    $html = ob_get_clean();
    $assert('Arabic OTP view ' . $method, preg_match('/[\x{0600}-\x{06ff}]/u', $html) === 1);
}
if (class_exists('WooCommerce')) {
    $_GET['tab'] = 'ersaal';
    $woocommerce = new \Ersaal\Modules\WooCommerce\WooCommerceModule($options, new \Ersaal\Services\MessageService($repository), $repository);
    $woocommerce->enqueueAssets('woocommerce_page_wc-settings');
    $data = wp_scripts()->get_data('ersaal-woocommerce-settings', 'data');
    $assert('Arabic WooCommerce JavaScript messages', is_string($data) && strpos($data, wp_json_encode(__('Characters', $domain))) !== false && __('Characters', $domain) !== 'Characters');
    $plugin->enqueueGlobalAdminAssets('woocommerce_page_wc-settings');
    $assert('WooCommerce shared styles', wp_style_is('ersaal-admin', 'enqueued'));
    unset($_GET['tab']);
}
restore_previous_locale();
$assert('English locale restored', determine_locale() === 'en_US');
$assert('English settings restored', __('Settings', $domain) === 'Settings');
$assert('LTR restored', !is_rtl());

update_user_meta($admins[0]->ID, 'locale', 'ar');
$assert('Arabic user on English site', get_option('WPLANG', 'en_US') !== 'ar' && determine_locale() === 'ar');
unload_textdomain($domain, true);
$plugin->loadTextdomain();
$assert('user Arabic translation', __('Settings', $domain) === 'الإعدادات');
delete_user_meta($admins[0]->ID, 'locale');
unload_textdomain($domain, true);
$plugin->loadTextdomain();

echo "{$checks} bundled localization checks passed.\n";
