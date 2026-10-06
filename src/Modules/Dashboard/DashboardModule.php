<?php
declare(strict_types=1);

namespace Ersaal\Modules\Dashboard;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Storage\LogRepository;
use Ersaal\Modules\OTP\OTPLogRepository;

class DashboardModule implements ModuleInterface
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function id(): string
    {
        return 'dashboard';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function register(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 9);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_menu_page(
            __('Ersaal SMS', 'ersaal'),
            __('Ersaal SMS', 'ersaal'),
            'manage_options',
            'ersaal-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-email-alt',
            56
        );

        add_submenu_page(
            'ersaal-dashboard',
            __('Dashboard', 'ersaal'),
            __('Dashboard', 'ersaal'),
            'manage_options',
            'ersaal-dashboard',
            [$this, 'renderDashboardPage']
        );
    }

    public function renderDashboardPage(): void
    {
        require_once ERSAAL_PLUGIN_DIR . 'admin/DashboardPage.php';
        
        $page = new \Ersaal\Admin\DashboardPage(new LogRepository(), new OTPLogRepository(), $this->options);
        $page->render();
    }
}
