<?php
declare(strict_types=1);

namespace Ersaal\Modules\Settings;

use Ersaal\API\Client;
use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Services\ConnectionStatusService;
use Ersaal\Support\Str;

class SettingsModule implements ModuleInterface
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function id(): string
    {
        return 'settings';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function register(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 10);
            add_action('admin_init', [$this, 'registerSettings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
            add_action('wp_ajax_ersaal_test_api', [$this, 'ajaxTestApi']);
        }
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'ersaal-settings') === false) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
        if ($tab !== 'general') {
            return;
        }

        wp_enqueue_script(
            'ersaal-settings-general',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/settings-general.js',
            ['jquery'],
            ERSAAL_VERSION,
            true
        );

        wp_localize_script('ersaal-settings-general', 'ersaalSettingsGeneral', [
            'nonce' => wp_create_nonce('ersaal_test_api'),
            'i18n'  => [
                'connectionFailed' => __('The connection test could not be completed.', 'ersaal-sms-gateway'),
            ],
        ]);
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('Settings', 'ersaal-sms-gateway'),
            __('Settings', 'ersaal-sms-gateway'),
            'manage_options',
            'ersaal-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('ersaal_general_settings', 'ersaal_api_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => 'https://api.ersaal.com/'
        ]);
        
        register_setting('ersaal_general_settings', 'ersaal_api_key', [
            'type' => 'string',
            'sanitize_callback' => function ($value) {
                if (defined('ERSAAL_API_KEY')) {
                    return get_option('ersaal_api_key', '');
                }
                $trimmed = trim((string) $value);
                if ($trimmed === '' || $trimmed === '********') {
                    return get_option('ersaal_api_key', '');
                }
                if (strncasecmp($trimmed, 'bearer ', 7) === 0) {
                    $trimmed = trim(substr($trimmed, 7));
                }
                return sanitize_text_field($trimmed);
            },
        ]);
        
        register_setting('ersaal_advanced_settings', 'ersaal_clean_on_uninstall', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ]);

        register_setting('ersaal_advanced_settings', 'ersaal_log_retention_days', [
            'type'              => 'integer',
            'sanitize_callback' => static function ($value): int {
                $int = (int) $value;
                // 0 = disabled; otherwise clamp to 1–3650 (10 years max)
                return ($int === 0) ? 0 : max(1, min(3650, $int));
            },
            'default' => 0,
        ]);
    }

    public function ajaxTestApi(): void
    {
        check_ajax_referer('ersaal_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ersaal-sms-gateway')]);
        }

        $client = new Client($this->options);
        try {
            $response = $client->getProjectDetails();
            $projectName = $response->getProjectName();
            if (!empty($projectName)) {
                /* translators: %s: Ersaal project name */
                $msg = sprintf(__('Connected successfully! Project: %s', 'ersaal-sms-gateway'), esc_html($projectName));
            } else {
                $msg = __('Connected successfully!', 'ersaal-sms-gateway');
            }
            (new ConnectionStatusService($this->options))->clearCache();
            wp_send_json_success(['message' => $msg]);
        } catch (\Exception $e) {
            $cleanError = sanitize_text_field($e->getMessage());
            $cleanError = Str::limit($cleanError, 255);
            wp_send_json_error(['message' => $cleanError]);
        }
    }

    public function renderSettingsPage(): void
    {
        require_once ERSAAL_PLUGIN_DIR . 'admin/SettingsPage.php';
        $page = new \Ersaal\Admin\SettingsPage($this->options);
        $page->render();
    }
}
