<?php
/**
 * Plugin Name: Ersaal SMS Gateway
 * Plugin URI: https://getersaal.com/
 * Description: Official Ersaal SMS Gateway integration for WordPress & WooCommerce. Send SMS notifications, verify phone numbers via OTP, and enable 2FA login for Libyan networks.
 * Version: 1.3.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Lamah
 * Author URI: https://lamah.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ersaal
 * Domain Path: /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('ERSAAL_VERSION', '1.3.0');
define('ERSAAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ERSAAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ERSAAL_PLUGIN_FILE', __FILE__);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/admin/ui.php';

function ersaal_plugin(): \Ersaal\Core\Plugin {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Ersaal\Core\Plugin();
    }
    return $instance;
}

/**
 * Return the shared OTP service for internal integrations.
 *
 * Call after the plugins_loaded hook. The service can be null while the
 * plugin is still booting.
 */
function ersaal_otp_service(): ?\Ersaal\Modules\OTP\OTPService {
    return ersaal_plugin()->getOtpService();
}

/**
 * Return whether the public SMS integration API is initialized and configured.
 */
function ersaal_sms_available(): bool {
    $facade = ersaal_plugin()->getSmsFacade();
    return $facade !== null && $facade->isAvailable();
}

/**
 * Queue an SMS through the public integration API.
 *
 * @param array<string, mixed> $request Public SMS request fields.
 */
function ersaal_send_sms(array $request): \Ersaal\PublicApi\SmsResult {
    $facade = ersaal_plugin()->getSmsFacade();
    if ($facade === null) {
        return \Ersaal\PublicApi\SmsResult::failure(
            'unavailable',
            'not_initialized',
            __('Ersaal SMS is not initialized.', 'ersaal'),
            sanitize_text_field((string) ($request['idempotency_key'] ?? ''))
        );
    }

    return $facade->send($request);
}

// Initialize the plugin
add_action('plugins_loaded', function () {
    if (class_exists(\Ersaal\Core\Plugin::class)) {
        ersaal_plugin()->boot();
    }
});

register_activation_hook(ERSAAL_PLUGIN_FILE, function () {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }
    if (class_exists(\Ersaal\Core\Activator::class)) {
        \Ersaal\Core\Activator::activate();
    }
});

register_deactivation_hook(ERSAAL_PLUGIN_FILE, function () {
    wp_clear_scheduled_hook('ersaal_daily_log_cleanup');
});

