<?php
declare(strict_types=1);

namespace Ersaal\Modules\Help;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;

class HelpModule implements ModuleInterface
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function id(): string
    {
        return 'help';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function register(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 99);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('Help & User Guide', 'ersaal'),
            __('Help & User Guide', 'ersaal'),
            'manage_options',
            'ersaal-help',
            [$this, 'renderHelpPage']
        );
    }

    public function renderHelpPage(): void
    {
        require_once ERSAAL_PLUGIN_DIR . 'admin/HelpPage.php';
        
        $page = new \Ersaal\Admin\HelpPage();
        $page->render();
    }
}
