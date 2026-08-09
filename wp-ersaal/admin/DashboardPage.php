<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Storage\LogRepository;
use Ersaal\Core\Options;
use Ersaal\Services\ConnectionStatusService;

class DashboardPage
{
    private LogRepository $repo;
    private Options $options;

    public function __construct(LogRepository $repo, Options $options)
    {
        $this->repo = $repo;
        $this->options = $options;
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
        $connection = (new ConnectionStatusService($this->options))->getStatus();

        require ERSAAL_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
