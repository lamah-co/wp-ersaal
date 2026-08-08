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
}
