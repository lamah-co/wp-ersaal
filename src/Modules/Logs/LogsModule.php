<?php
declare(strict_types=1);

namespace Ersaal\Modules\Logs;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Storage\LogRepository;

class LogsModule implements ModuleInterface
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function id(): string
    {
        return 'logs';
    }

    public function isActive(): bool
    {
        return true; // Logs module is always active
    }

    public function register(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 20);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('Ersaal Logs', 'ersaal-sms-gateway'),
            __('Logs', 'ersaal-sms-gateway'),
            'manage_options',
            'ersaal-logs',
            [$this, 'renderLogsPage']
        );
    }

    public function renderLogsPage(): void
    {
        require_once ERSAAL_PLUGIN_DIR . 'admin/LogsPage.php';
        
        $page = new \Ersaal\Admin\LogsPage(new LogRepository());
        $page->render();
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'ersaal-logs') === false) {
            return;
        }

        wp_enqueue_script(
            'ersaal-logs',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/logs.js',
            [],
            ERSAAL_VERSION,
            true
        );

        wp_localize_script('ersaal-logs', 'ersaalLogs', [
            'currency_symbol' => 'د.ل',
            'i18n' => [
                'copied' => __('Copied', 'ersaal-sms-gateway'),
                'copy_id' => __('Copy message ID', 'ersaal-sms-gateway'),
                'copy_message' => __('Copy message', 'ersaal-sms-gateway'),
                'delete_confirm' => __('Are you sure you want to delete this log? This only deletes the local record.', 'ersaal-sms-gateway'),
                'fields' => [
                    'id' => __('Log ID', 'ersaal-sms-gateway'),
                    'status' => __('Status', 'ersaal-sms-gateway'),
                    'source' => __('Source', 'ersaal-sms-gateway'),
                    'source_id' => __('Source ID', 'ersaal-sms-gateway'),
                    'source_event' => __('Event', 'ersaal-sms-gateway'),
                    'phone_masked' => __('Masked phone', 'ersaal-sms-gateway'),
                    'message_id' => __('Message ID', 'ersaal-sms-gateway'),
                    'parts_final' => __('Parts', 'ersaal-sms-gateway'),
                    'cost_final' => __('Cost', 'ersaal-sms-gateway'),
                    'attempts' => __('Attempts', 'ersaal-sms-gateway'),
                    'api_http_code' => __('HTTP code', 'ersaal-sms-gateway'),
                    'api_error' => __('API error', 'ersaal-sms-gateway'),
                    'created_at' => __('Created', 'ersaal-sms-gateway'),
                    'updated_at' => __('Updated', 'ersaal-sms-gateway'),
                ],
            ],
        ]);
    }
}
