<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Storage\LogRepository;

class DashboardPage
{
    private LogRepository $repo;

    public function __construct(LogRepository $repo)
    {
        $this->repo = $repo;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'ersaal'));
        }

        $stats = $this->repo->getDashboardStats();
        $recent = $this->repo->getLogs([
            'per_page' => 10,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);

        require ERSAAL_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
