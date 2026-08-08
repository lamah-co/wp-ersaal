<?php
declare(strict_types=1);

namespace Ersaal\Modules\Settings;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\API\Client;

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
            add_action('wp_ajax_ersaal_test_api', [$this, 'ajaxTestApi']);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('Settings', 'ersaal'),
            __('Settings', 'ersaal'),
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
    }

    public function ajaxTestApi(): void
    {
        check_ajax_referer('ersaal_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ersaal')]);
        }

        $client = new Client($this->options);
        try {
            $response = $client->getProjectDetails();
            $projectName = $response->getProjectName();
            if (!empty($projectName)) {
                $msg = sprintf(__('Connected successfully! Project: %s', 'ersaal'), esc_html($projectName));
            } else {
                $msg = __('Connected successfully!', 'ersaal');
            }
            wp_send_json_success(['message' => $msg]);
        } catch (\Exception $e) {
            $cleanError = sanitize_text_field($e->getMessage());
            if (mb_strlen($cleanError) > 255) {
                $cleanError = mb_substr($cleanError, 0, 252) . '...';
            }
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
