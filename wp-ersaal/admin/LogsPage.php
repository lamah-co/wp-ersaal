<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Storage\LogRepository;

class LogsPage
{
    private LogRepository $repository;

    public function __construct(LogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'ersaal'));
        }
        
        $stats = $this->repository->getLogsStats();
        
        $args = [
            'page' => isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all',
            'source' => isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'all',
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
        ];
        
        $logsData = $this->repository->getLogs($args);

        require ERSAAL_PLUGIN_DIR . 'admin/views/logs.php';
    }
}
