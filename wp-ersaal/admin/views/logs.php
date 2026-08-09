<?php
if (!defined('ABSPATH')) {
    exit;
}

$pageUrl = admin_url('admin.php?page=ersaal-logs');
$currentStatus = $args['status'] ?? 'all';
$currentSource = $args['source'] ?? 'all';
$currentEvent = $args['event'] ?? 'all';
$dateFrom = $args['date_from'] ?? '';
$dateTo = $args['date_to'] ?? '';
$searchQuery = $args['search'] ?? '';
$hasFilters = $currentStatus !== 'all' || $currentSource !== 'all' || $currentEvent !== 'all' || $dateFrom !== '' || $dateTo !== '' || $searchQuery !== '';
$activeFilterCount = count(array_filter([
    $currentStatus !== 'all',
    $currentSource !== 'all',
    $currentEvent !== 'all',
    $dateFrom !== '',
    $dateTo !== '',
    $searchQuery !== '',
]));

$statusBadge = static function (string $status): string {
    $map = [
        'processing' => 'info',
        'accepted' => 'success',
        'delivered' => 'success',
        'sent' => 'success',
        'retry_scheduled' => 'warning',
        'queued' => 'warning',
        'pending' => 'warning',
        'failed' => 'danger',
        'error' => 'danger',
        'rejected' => 'danger',
    ];
    $type = $map[$status] ?? 'muted';
    $label = ersaal_admin_status_label($status);

    return sprintf('<span class="ersaal-badge ersaal-badge-%s">%s</span>', esc_attr($type), esc_html($label));
};

$referenceHtml = static function (object $log): string {
    $meta = sprintf('<span class="ersaal-table-meta ersaal-ltr">%s</span>', esc_html(sprintf(__('Log #%s', 'ersaal'), (string) $log->id)));
    if ($log->source === 'woocommerce' && !empty($log->source_id)) {
        $label = sprintf(__('Order #%s', 'ersaal'), $log->source_id);
        $link = function_exists('get_edit_post_link') ? get_edit_post_link((int) $log->source_id) : '';
        $primary = $link
            ? sprintf('<a href="%s"><strong>%s</strong></a>', esc_url($link), esc_html($label))
            : sprintf('<strong>%s</strong>', esc_html($label));
        return '<span class="ersaal-log-reference">' . $primary . $meta . '</span>';
    }

    return sprintf('<span class="ersaal-log-reference"><strong>%s</strong>%s</span>', esc_html(ersaal_admin_source_label((string) $log->source)), $meta);
};

$failedCount = ($stats['failed'] ?? 0) + ($stats['error'] ?? 0);
$notices = get_settings_errors('ersaal_logs');
?>

<div class="wrap ersaal-admin ersaal-page ersaal-page-wide ersaal-logs-page">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('Logs', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Track SMS activity and troubleshoot requests.', 'ersaal'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <form method="get" action="admin.php">
                <input type="hidden" name="page" value="ersaal-logs" />
                <?php foreach (['status' => $currentStatus, 'source' => $currentSource, 'event' => $currentEvent, 'date_from' => $dateFrom, 'date_to' => $dateTo, 's' => $searchQuery] as $key => $value): ?>
                    <?php if ($value !== '' && $value !== 'all'): ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" />
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php wp_nonce_field('ersaal_export_csv'); ?>
                <input type="hidden" name="ersaal_export_csv" value="1" />
                <button type="submit" class="ersaal-btn ersaal-btn-secondary">
                    <?php echo ersaal_admin_icon('download'); ?>
                    <?php esc_html_e('Export CSV', 'ersaal'); ?>
                </button>
            </form>
        </div>
    </header>

    <?php foreach ($notices as $notice): ?>
        <?php $noticeClass = in_array($notice['type'], ['success', 'updated'], true) ? 'ersaal-alert-success' : 'ersaal-alert-danger'; ?>
        <div class="ersaal-alert <?php echo esc_attr($noticeClass); ?>" role="status"><p><?php echo esc_html($notice['message']); ?></p></div>
    <?php endforeach; ?>

    <section class="ersaal-section" aria-label="<?php esc_attr_e('Log summary', 'ersaal'); ?>">
        <div class="ersaal-log-summary">
            <div class="ersaal-log-summary-item is-total">
                <span class="ersaal-log-summary-label"><span class="ersaal-log-summary-dot" aria-hidden="true"></span><?php esc_html_e('Total', 'ersaal'); ?></span>
                <strong class="ersaal-log-summary-value"><?php echo esc_html(number_format_i18n($stats['total'] ?? 0)); ?></strong>
                <span class="ersaal-log-summary-context"><?php esc_html_e('All recorded requests', 'ersaal'); ?></span>
            </div>
            <div class="ersaal-log-summary-item is-success">
                <span class="ersaal-log-summary-label"><span class="ersaal-log-summary-dot" aria-hidden="true"></span><?php esc_html_e('Accepted', 'ersaal'); ?></span>
                <strong class="ersaal-log-summary-value"><?php echo esc_html(number_format_i18n($stats['accepted'] ?? 0)); ?></strong>
                <span class="ersaal-log-summary-context"><?php esc_html_e('Received by Ersaal', 'ersaal'); ?></span>
            </div>
            <div class="ersaal-log-summary-item is-danger">
                <span class="ersaal-log-summary-label"><span class="ersaal-log-summary-dot" aria-hidden="true"></span><?php esc_html_e('Failed', 'ersaal'); ?></span>
                <strong class="ersaal-log-summary-value"><?php echo esc_html(number_format_i18n($failedCount)); ?></strong>
                <span class="ersaal-log-summary-context"><?php esc_html_e('Needs attention', 'ersaal'); ?></span>
            </div>
            <div class="ersaal-log-summary-item is-warning">
                <span class="ersaal-log-summary-label"><span class="ersaal-log-summary-dot" aria-hidden="true"></span><?php esc_html_e('Retries', 'ersaal'); ?></span>
                <strong class="ersaal-log-summary-value"><?php echo esc_html(number_format_i18n($stats['retry_scheduled'] ?? 0)); ?></strong>
                <span class="ersaal-log-summary-context"><?php esc_html_e('Scheduled again', 'ersaal'); ?></span>
            </div>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-log-filters-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-log-filters-title" class="ersaal-section-title"><?php esc_html_e('Find messages', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('Search by reference, then narrow the results only when needed.', 'ersaal'); ?></p>
            </div>
            <?php if ($activeFilterCount > 0): ?>
                <span class="ersaal-filter-count"><?php printf(esc_html(_n('%s active filter', '%s active filters', $activeFilterCount, 'ersaal')), esc_html(number_format_i18n($activeFilterCount))); ?></span>
            <?php endif; ?>
        </div>
        <div class="ersaal-toolbar">
            <form method="get" action="admin.php" class="ersaal-filter-form">
                <input type="hidden" name="page" value="ersaal-logs" />
                <div class="ersaal-field ersaal-filter-search">
                    <label class="ersaal-label" for="ersaal-log-search"><?php esc_html_e('Search', 'ersaal'); ?></label>
                    <input id="ersaal-log-search" type="search" name="s" value="<?php echo esc_attr($searchQuery); ?>" class="ersaal-input" placeholder="<?php esc_attr_e('Log ID, message ID, order or phone', 'ersaal'); ?>" />
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal-log-status"><?php esc_html_e('Status', 'ersaal'); ?></label>
                    <select id="ersaal-log-status" name="status" class="ersaal-select">
                        <option value="all" <?php selected($currentStatus, 'all'); ?>><?php esc_html_e('All statuses', 'ersaal'); ?></option>
                        <option value="processing" <?php selected($currentStatus, 'processing'); ?>><?php esc_html_e('Processing', 'ersaal'); ?></option>
                        <option value="accepted" <?php selected($currentStatus, 'accepted'); ?>><?php esc_html_e('Accepted', 'ersaal'); ?></option>
                        <option value="retry_scheduled" <?php selected($currentStatus, 'retry_scheduled'); ?>><?php esc_html_e('Retry scheduled', 'ersaal'); ?></option>
                        <option value="failed" <?php selected($currentStatus, 'failed'); ?>><?php esc_html_e('Failed', 'ersaal'); ?></option>
                        <option value="error" <?php selected($currentStatus, 'error'); ?>><?php esc_html_e('Error', 'ersaal'); ?></option>
                    </select>
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal-log-source"><?php esc_html_e('Source', 'ersaal'); ?></label>
                    <select id="ersaal-log-source" name="source" class="ersaal-select">
                        <option value="all" <?php selected($currentSource, 'all'); ?>><?php esc_html_e('All sources', 'ersaal'); ?></option>
                        <option value="manual" <?php selected($currentSource, 'manual'); ?>><?php esc_html_e('Manual', 'ersaal'); ?></option>
                        <option value="woocommerce" <?php selected($currentSource, 'woocommerce'); ?>><?php esc_html_e('WooCommerce', 'ersaal'); ?></option>
                    </select>
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal-log-event"><?php esc_html_e('Event', 'ersaal'); ?></label>
                    <select id="ersaal-log-event" name="event" class="ersaal-select">
                        <option value="all" <?php selected($currentEvent, 'all'); ?>><?php esc_html_e('All events', 'ersaal'); ?></option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo esc_attr($event); ?>" <?php selected($currentEvent, $event); ?>><?php echo esc_html(ersaal_admin_event_label((string) $event)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal-log-date-from"><?php esc_html_e('From', 'ersaal'); ?></label>
                    <input id="ersaal-log-date-from" type="date" name="date_from" value="<?php echo esc_attr($dateFrom); ?>" class="ersaal-input ersaal-ltr" />
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal-log-date-to"><?php esc_html_e('To', 'ersaal'); ?></label>
                    <input id="ersaal-log-date-to" type="date" name="date_to" value="<?php echo esc_attr($dateTo); ?>" class="ersaal-input ersaal-ltr" />
                </div>
                <div class="ersaal-filter-actions">
                    <button type="submit" class="ersaal-btn ersaal-btn-primary ersaal-btn-prominent"><?php echo ersaal_admin_icon('search'); ?><?php esc_html_e('Apply filters', 'ersaal'); ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?php echo esc_url($pageUrl); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm"><?php esc_html_e('Reset', 'ersaal'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-log-results-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-log-results-title" class="ersaal-section-title"><?php esc_html_e('Message activity', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php printf(esc_html(_n('%s matching record', '%s matching records', $logsData['total'], 'ersaal')), esc_html(number_format_i18n($logsData['total']))); ?></p>
            </div>
        </div>

        <?php if (empty($logsData['items'])): ?>
            <div class="ersaal-card ersaal-card-flat">
                <div class="ersaal-empty-state">
                    <?php echo ersaal_admin_icon('logs'); ?>
                    <div>
                        <p class="ersaal-empty-state-title"><?php echo esc_html($hasFilters ? __('No messages match these filters', 'ersaal') : __('No messages yet', 'ersaal')); ?></p>
                        <p class="ersaal-empty-state-text"><?php echo esc_html($hasFilters ? __('Reset the filters or broaden your search.', 'ersaal') : __('Messages sent through Ersaal will appear here.', 'ersaal')); ?></p>
                    </div>
                    <?php if ($hasFilters): ?>
                        <a href="<?php echo esc_url($pageUrl); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm"><?php esc_html_e('Reset filters', 'ersaal'); ?></a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-send-message')); ?>" class="ersaal-btn ersaal-btn-primary ersaal-btn-sm"><?php esc_html_e('Send SMS', 'ersaal'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo esc_url($pageUrl); ?>" id="ersaal-logs-form">
                <?php wp_nonce_field('ersaal_bulk_logs'); ?>
                <div class="ersaal-bulk-bar">
                    <div class="ersaal-bulk-actions">
                        <label for="ersaal-bulk-action" class="screen-reader-text"><?php esc_html_e('Bulk action', 'ersaal'); ?></label>
                        <select name="action" id="ersaal-bulk-action" class="ersaal-select">
                            <option value="-1"><?php esc_html_e('Bulk actions', 'ersaal'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete', 'ersaal'); ?></option>
                        </select>
                        <button type="submit" class="ersaal-btn ersaal-btn-secondary ersaal-btn-bulk-apply" name="ersaal_bulk_action" value="1" data-confirm="<?php esc_attr_e('Are you sure you want to delete selected logs?', 'ersaal'); ?>" disabled><?php echo ersaal_admin_icon('check'); ?><?php esc_html_e('Apply', 'ersaal'); ?></button>
                    </div>
                    <span class="ersaal-table-meta"><?php printf(esc_html(_n('%s item', '%s items', $logsData['total'], 'ersaal')), esc_html(number_format_i18n($logsData['total']))); ?></span>
                </div>

                <div class="ersaal-table-wrap">
                    <table class="wp-list-table widefat ersaal-table ersaal-log-table">
                        <thead>
                            <tr>
                                <td class="column-check check-column">
                                    <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select all', 'ersaal'); ?></label>
                                    <input id="cb-select-all-1" type="checkbox" />
                                </td>
                                <th class="column-status"><?php esc_html_e('Status', 'ersaal'); ?></th>
                                <th class="column-reference"><?php esc_html_e('Reference', 'ersaal'); ?></th>
                                <th class="column-phone"><?php esc_html_e('Phone', 'ersaal'); ?></th>
                                <th class="column-message"><?php esc_html_e('Message', 'ersaal'); ?></th>
                                <th class="column-meta"><?php esc_html_e('Delivery', 'ersaal'); ?></th>
                                <th class="column-created"><?php esc_html_e('Created', 'ersaal'); ?></th>
                                <th class="column-actions"><?php esc_html_e('Actions', 'ersaal'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logsData['items'] as $log): ?>
                                <?php
                                $logDetails = (array) $log;
                                $logDetails['status'] = ersaal_admin_status_label((string) $log->status);
                                $logDetails['source'] = ersaal_admin_source_label((string) $log->source);
                                $logDetails['source_event'] = !empty($log->source_event) ? ersaal_admin_event_label((string) $log->source_event) : '';
                                ?>
                                <tr class="ersaal-log-row">
                                    <th scope="row" class="column-check check-column"><input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>" /></th>
                                    <td><?php echo $statusBadge((string) $log->status); ?></td>
                                    <td><?php echo $referenceHtml($log); ?></td>
                                    <td><span class="ersaal-code ersaal-ltr ersaal-log-phone"><?php echo esc_html($log->phone_masked); ?></span></td>
                                    <td><div class="ersaal-table-message ersaal-log-message"><?php echo !empty($log->message_excerpt) ? esc_html($log->message_excerpt) : '&mdash;'; ?></div></td>
                                    <td>
                                        <div class="ersaal-log-delivery">
                                            <?php if (!empty($log->message_id)): ?>
                                                <div class="ersaal-inline-actions">
                                                    <span class="ersaal-code ersaal-ltr" title="<?php echo esc_attr($log->message_id); ?>"><?php echo esc_html(mb_substr((string) $log->message_id, 0, 8)); ?>&hellip;</span>
                                                    <button type="button" class="ersaal-btn ersaal-btn-ghost ersaal-icon-button ersaal-copy-id" data-clipboard="<?php echo esc_attr($log->message_id); ?>" aria-label="<?php esc_attr_e('Copy message ID', 'ersaal'); ?>"><?php echo ersaal_admin_icon('copy'); ?></button>
                                                </div>
                                            <?php else: ?>
                                                <span class="ersaal-table-meta">&mdash;</span>
                                            <?php endif; ?>
                                            <span class="ersaal-table-meta"><?php printf(esc_html__('Parts %1$s · Attempts %2$s', 'ersaal'), esc_html((string) ($log->parts_final ?? '—')), esc_html((string) $log->attempts)); ?></span>
                                        </div>
                                    </td>
                                    <td><time class="ersaal-table-meta ersaal-ltr ersaal-log-created" datetime="<?php echo esc_attr($log->created_at); ?>"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></time></td>
                                    <td>
                                        <div class="ersaal-inline-actions ersaal-log-actions">
                                            <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm ersaal-view-log" data-log="<?php echo esc_attr(wp_json_encode($logDetails)); ?>"><?php esc_html_e('Details', 'ersaal'); ?></button>
                                            <button type="button" class="ersaal-btn ersaal-btn-danger ersaal-icon-button ersaal-delete-log" data-log-id="<?php echo esc_attr($log->id); ?>" aria-label="<?php esc_attr_e('Delete log', 'ersaal'); ?>"><?php echo ersaal_admin_icon('trash'); ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($logsData['pages'] > 1): ?>
                    <nav class="ersaal-pagination" aria-label="<?php esc_attr_e('Logs pagination', 'ersaal'); ?>">
                        <?php
                        echo wp_kses_post(paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('Previous', 'ersaal'),
                            'next_text' => __('Next', 'ersaal'),
                            'total' => $logsData['pages'],
                            'current' => $logsData['page'],
                        ]));
                        ?>
                    </nav>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </section>

    <form method="post" id="ersaal-single-delete-form" hidden>
        <?php wp_nonce_field('ersaal_delete_log'); ?>
        <input type="hidden" name="ersaal_delete_log" value="1" />
        <input type="hidden" name="log_id" id="ersaal-single-delete-id" value="" />
    </form>

    <div id="ersaal-log-modal" class="ersaal-modal" aria-hidden="true">
        <section class="ersaal-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ersaal-log-modal-title" tabindex="-1">
            <header class="ersaal-modal-header">
                <h2 id="ersaal-log-modal-title" class="ersaal-modal-title"><?php esc_html_e('Log details', 'ersaal'); ?></h2>
                <button type="button" id="ersaal-close-modal" class="ersaal-btn ersaal-btn-ghost ersaal-icon-button" aria-label="<?php esc_attr_e('Close details', 'ersaal'); ?>"><?php echo ersaal_admin_icon('close'); ?></button>
            </header>
            <div class="ersaal-modal-body">
                <dl id="ersaal-log-detail-list" class="ersaal-detail-list"></dl>
                <div id="ersaal-log-message-section" class="ersaal-section">
                    <h3 class="ersaal-card-title"><?php esc_html_e('Message', 'ersaal'); ?></h3>
                    <div id="ersaal-log-message" class="ersaal-message-block"></div>
                    <button type="button" id="ersaal-copy-message" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm"><?php echo ersaal_admin_icon('copy'); ?><?php esc_html_e('Copy message', 'ersaal'); ?></button>
                </div>
            </div>
        </section>
    </div>
</div>
