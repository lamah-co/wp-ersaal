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

        $this->handleActions();
        
        $stats = $this->repository->getLogsStats();
        
        $args = [
            'page' => isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1,
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all',
            'source' => isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'all',
            'event' => isset($_GET['event']) ? sanitize_text_field($_GET['event']) : 'all',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
        ];
        
        $logsData = $this->repository->getLogs($args);
        $events = $this->repository->getDistinctEvents();
        $sources = ersaal_admin_log_sources();
        foreach ($this->repository->getDistinctSources() as $source) {
            $source = sanitize_key((string) $source);
            if ($source !== '' && !isset($sources[$source])) {
                $sources[$source] = ersaal_admin_source_label($source);
            }
        }

        require ERSAAL_PLUGIN_DIR . 'admin/views/logs.php';
    }

    private function handleActions(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['ersaal_delete_log']) && isset($_POST['log_id'])) {
                check_admin_referer('ersaal_delete_log');
                $id = (int)$_POST['log_id'];
                if ($this->repository->deleteById($id)) {
                    add_settings_error('ersaal_logs', 'log_deleted', __('Log deleted successfully.', 'ersaal'), 'success');
                } else {
                    add_settings_error('ersaal_logs', 'log_delete_failed', __('Unable to delete selected log.', 'ersaal'), 'error');
                }
            } elseif (isset($_POST['ersaal_bulk_action']) && isset($_POST['log_ids']) && is_array($_POST['log_ids'])) {
                check_admin_referer('ersaal_bulk_logs');
                $action = sanitize_text_field($_POST['action'] ?? ($_POST['action2'] ?? ''));
                if ($action === 'delete') {
                    $ids = array_map('intval', $_POST['log_ids']);
                    $count = $this->repository->deleteBulk($ids);
                    if ($count > 0) {
                        add_settings_error('ersaal_logs', 'logs_bulk_deleted', sprintf(_n('%s log deleted successfully.', '%s logs deleted successfully.', $count, 'ersaal'), $count), 'success');
                    } else {
                        add_settings_error('ersaal_logs', 'logs_bulk_delete_failed', __('Unable to delete selected logs.', 'ersaal'), 'error');
                    }
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ersaal_export_csv'])) {
            check_admin_referer('ersaal_export_csv');
            $this->exportCsv();
        }
    }

    private function exportCsv(): void
    {
        $args = [
            'per_page' => -1, // all matching records
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all',
            'source' => isset($_GET['source']) ? sanitize_text_field($_GET['source']) : 'all',
            'event' => isset($_GET['event']) ? sanitize_text_field($_GET['event']) : 'all',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC'
        ];

        $logsData = $this->repository->getLogs($args);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ersaal-logs-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Add BOM for UTF-8 Excel support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            __('Log ID', 'ersaal'), __('Status', 'ersaal'), __('Source', 'ersaal'), __('Source ID', 'ersaal'), __('Source event', 'ersaal'),
            __('Masked phone', 'ersaal'), __('Message', 'ersaal'), __('Message ID', 'ersaal'), __('Parts', 'ersaal'), __('Cost', 'ersaal'),
            __('Attempts', 'ersaal'), __('HTTP code', 'ersaal'), __('API error', 'ersaal'), __('Created at', 'ersaal'), __('Updated at', 'ersaal')
        ]);

        foreach ($logsData['items'] as $log) {
            fputcsv($output, [
                $log->id,
                ersaal_admin_status_label((string) $log->status),
                ersaal_admin_source_label((string) $log->source),
                $log->source_id,
                !empty($log->source_event) ? ersaal_admin_event_label((string) $log->source_event) : '',
                $log->phone_masked,
                $log->message_text ?: $log->message_excerpt,
                $log->message_id,
                $log->parts_final,
                $log->cost_final,
                $log->attempts,
                $log->api_http_code,
                $log->api_error,
                $log->created_at,
                $log->updated_at
            ]);
        }

        fclose($output);
        exit;
    }
}
