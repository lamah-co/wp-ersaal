<?php
/**
 * Plugin Name: Ersaal SMS Gateway
 * Description: Ersaal SMS Gateway integration for WordPress and WooCommerce.
 * Version: 1.0.2
 * Author: Lamah
 * Author URI: https://lamah.co/
 * Text Domain: ersaal
 * Domain Path: /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('ERSAAL_VERSION', '1.0.2');
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
