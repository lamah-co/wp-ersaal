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
            __('Ersaal Logs', 'ersaal'),
            __('Logs', 'ersaal'),
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
            'i18n' => [
                'copied' => __('Copied', 'ersaal'),
                'copy_id' => __('Copy message ID', 'ersaal'),
                'copy_message' => __('Copy message', 'ersaal'),
                'delete_confirm' => __('Are you sure you want to delete this log? This only deletes the local record.', 'ersaal'),
                'fields' => [
                    'id' => __('Log ID', 'ersaal'),
                    'status' => __('Status', 'ersaal'),
                    'source' => __('Source', 'ersaal'),
                    'source_id' => __('Source ID', 'ersaal'),
                    'source_event' => __('Event', 'ersaal'),
                    'phone_masked' => __('Masked phone', 'ersaal'),
                    'message_id' => __('Message ID', 'ersaal'),
                    'parts_final' => __('Parts', 'ersaal'),
                    'cost_final' => __('Cost', 'ersaal'),
                    'attempts' => __('Attempts', 'ersaal'),
                    'api_http_code' => __('HTTP code', 'ersaal'),
                    'api_error' => __('API error', 'ersaal'),
                    'created_at' => __('Created', 'ersaal'),
                    'updated_at' => __('Updated', 'ersaal'),
                ],
            ],
        ]);
    }
}
