<?php
if (!defined('ABSPATH')) {
    exit;
}

$page_url = admin_url('admin.php?page=ersaal-logs');
$current_status = $args['status'] ?? 'all';
$current_source = $args['source'] ?? 'all';
$search_query = $args['search'] ?? '';

function get_status_badge(string $status): string {
    $map = [
        'processing' => 'info',
        'accepted' => 'success',
        'retry_scheduled' => 'warning',
        'failed' => 'danger',
        'error' => 'danger'
    ];
    $type = $map[$status] ?? 'info';
    return sprintf('<span class="ersaal-badge ersaal-badge-%s">%s</span>', esc_attr($type), esc_html(strtoupper($status)));
}

function get_reference_html(object $log): string {
    if ($log->source === 'woocommerce' && !empty($log->source_id)) {
        if (function_exists('get_edit_post_link')) {
            $link = get_edit_post_link((int)$log->source_id);
            if ($link) {
                return sprintf('<a href="%s" style="font-weight:var(--ersaal-fw-bold);">Order #%s</a>', esc_url($link), esc_html($log->source_id));
            }
        }
        return sprintf('<strong style="font-weight:var(--ersaal-fw-bold);">Order #%s</strong>', esc_html($log->source_id));
    }
    return esc_html(ucfirst($log->source));
}
?>

<div class="wrap ersaal-admin ersaal-page">
    <div class="ersaal-page-header">
        <div>
            <h1 class="ersaal-page-title"><?php esc_html_e('SMS Logs', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Track SMS requests sent through Ersaal.', 'ersaal'); ?></p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#logs')); ?>" class="ersaal-btn ersaal-btn-ghost">
                <span class="dashicons dashicons-editor-help"></span>
                <?php esc_html_e('Need help?', 'ersaal'); ?>
            </a>
        </div>
    </div>

    <?php settings_errors('ersaal_logs'); ?>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--ersaal-space-md); margin-block-end: var(--ersaal-space-xl);">
        <?php
        $cards = [
            ['label' => 'Total', 'key' => 'total', 'color' => 'var(--ersaal-text-primary)'],
            ['label' => 'Accepted', 'key' => 'accepted', 'color' => 'var(--ersaal-color-success)'],
            ['label' => 'Failed/Error', 'keys' => ['failed', 'error'], 'color' => 'var(--ersaal-color-danger)'],
            ['label' => 'Retry', 'key' => 'retry_scheduled', 'color' => 'var(--ersaal-color-warning)'],
            ['label' => 'Processing', 'key' => 'processing', 'color' => 'var(--ersaal-color-info)'],
        ];
        
        foreach ($cards as $card): 
            $count = 0;
            if (isset($card['keys'])) {
                foreach ($card['keys'] as $k) { $count += $stats[$k] ?? 0; }
            } else {
                $count = $stats[$card['key']] ?? 0;
            }
        ?>
            <div class="ersaal-card" style="padding: var(--ersaal-space-lg); border-inline-start: 4px solid <?php echo esc_attr($card['color']); ?>; box-shadow: var(--ersaal-shadow-sm);">
                <div style="font-size: var(--ersaal-text-sm); color: var(--ersaal-text-secondary); font-weight: var(--ersaal-fw-semibold); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($card['label']); ?></div>
                <div style="font-size: 28px; font-weight: var(--ersaal-fw-light); margin-top: var(--ersaal-space-xs); color: var(--ersaal-text-primary);"><?php echo number_format_i18n($count); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters & Search -->
    <div class="ersaal-card" style="margin-block-end: var(--ersaal-space-xl); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: var(--ersaal-space-md); padding: var(--ersaal-space-md);">
        <form method="get" action="admin.php" style="display: flex; gap: var(--ersaal-space-sm); align-items: center; flex-wrap: wrap; margin: 0;">
            <input type="hidden" name="page" value="ersaal-logs">
            
            <select name="status" class="ersaal-select" style="min-width: 140px; padding-block: 4px; height: auto;">
                <option value="all" <?php selected($current_status, 'all'); ?>><?php esc_html_e('All Statuses', 'ersaal'); ?></option>
                <option value="processing" <?php selected($current_status, 'processing'); ?>><?php esc_html_e('Processing', 'ersaal'); ?></option>
                <option value="accepted" <?php selected($current_status, 'accepted'); ?>><?php esc_html_e('Accepted', 'ersaal'); ?></option>
                <option value="retry_scheduled" <?php selected($current_status, 'retry_scheduled'); ?>><?php esc_html_e('Retry Scheduled', 'ersaal'); ?></option>
                <option value="failed" <?php selected($current_status, 'failed'); ?>><?php esc_html_e('Failed', 'ersaal'); ?></option>
                <option value="error" <?php selected($current_status, 'error'); ?>><?php esc_html_e('Error', 'ersaal'); ?></option>
            </select>
            
            <select name="source" class="ersaal-select" style="min-width: 140px; padding-block: 4px; height: auto;">
                <option value="all" <?php selected($current_source, 'all'); ?>><?php esc_html_e('All Sources', 'ersaal'); ?></option>
                <option value="manual" <?php selected($current_source, 'manual'); ?>><?php esc_html_e('Manual', 'ersaal'); ?></option>
                <option value="woocommerce" <?php selected($current_source, 'woocommerce'); ?>><?php esc_html_e('WooCommerce', 'ersaal'); ?></option>
            </select>

            <?php if (!empty($events)): ?>
            <select name="event" class="ersaal-select" style="min-width: 140px; padding-block: 4px; height: auto;">
                <option value="all" <?php selected($_GET['event'] ?? 'all', 'all'); ?>><?php esc_html_e('All Events', 'ersaal'); ?></option>
                <?php foreach ($events as $evt): 
                    $label = ucwords(str_replace('_', ' ', $evt));
                ?>
                <option value="<?php echo esc_attr($evt); ?>" <?php selected($_GET['event'] ?? '', $evt); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" class="ersaal-input" placeholder="<?php esc_attr_e('From Date', 'ersaal'); ?>" style="padding-block: 4px; height: auto;">
            <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" class="ersaal-input" placeholder="<?php esc_attr_e('To Date', 'ersaal'); ?>" style="padding-block: 4px; height: auto;">
            
            <button type="submit" class="ersaal-btn ersaal-btn-secondary" style="padding-block: 4px; height: auto;"><?php esc_html_e('Filter', 'ersaal'); ?></button>
            
            <?php if (isset($_GET['status']) || isset($_GET['source']) || isset($_GET['event']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['s'])): ?>
                <a href="<?php echo esc_url($page_url); ?>" class="ersaal-btn ersaal-btn-ghost" style="padding-block: 4px; height: auto;"><?php esc_html_e('Clear Filters', 'ersaal'); ?></a>
            <?php endif; ?>
        </form>
        
        <div style="display: flex; gap: var(--ersaal-space-md); align-items: center; flex-wrap: wrap;">
            <form method="get" action="admin.php" style="display: flex; gap: var(--ersaal-space-sm); align-items: center; margin: 0;">
                <input type="hidden" name="page" value="ersaal-logs">
                <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
                <input type="hidden" name="source" value="<?php echo esc_attr($current_source); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" class="ersaal-input" placeholder="<?php esc_attr_e('ID, Msg ID, Order, Phone', 'ersaal'); ?>" style="min-width: 200px; padding-block: 4px; height: auto;">
                <button type="submit" class="ersaal-btn ersaal-btn-secondary" style="padding-block: 4px; height: auto;"><?php esc_html_e('Search', 'ersaal'); ?></button>
            </form>

            <form method="get" action="admin.php" style="margin: 0;">
                <input type="hidden" name="page" value="ersaal-logs">
                <?php 
                foreach ($_GET as $k => $v) {
                    if (!in_array($k, ['page', 'ersaal_export_csv', '_wpnonce'])) {
                        echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
                    }
                }
                wp_nonce_field('ersaal_export_csv');
                ?>
                <input type="hidden" name="ersaal_export_csv" value="1">
                <button type="submit" class="ersaal-btn ersaal-btn-secondary" style="padding-block: 4px; height: auto;">
                    <span class="dashicons dashicons-download" style="margin-right: 4px;"></span>
                    <?php esc_html_e('Export CSV', 'ersaal'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <?php if (empty($logsData['items'])): ?>
        <div style="background: #fff; padding: 40px; text-align: center; border: 1px solid #ccd0d4;">
            <span class="dashicons dashicons-testimonial" style="font-size: 40px; width: 40px; height: 40px; color: #ccc;"></span>
            <p style="font-size: 16px; color: #666; margin-top: 15px;">
                <?php esc_html_e('No SMS logs found. Messages sent through Ersaal will appear here.', 'ersaal'); ?>
            </p>
        </div>
    <?php else: ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" id="ersaal-logs-form">
        <?php wp_nonce_field('ersaal_bulk_logs'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'ersaal'); ?></label>
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php esc_html_e('Bulk actions', 'ersaal'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'ersaal'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'ersaal'); ?>" name="ersaal_bulk_action" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete selected logs?', 'ersaal'); ?>');">
            </div>
            
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $logsData['total'], 'ersaal'), number_format_i18n($logsData['total'])); ?></span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e('Select All', 'ersaal'); ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 120px;">Reference</th>
                    <th style="width: 140px;">Phone</th>
                    <th style="width: 200px;">Message</th>
                    <th style="width: 150px;">Message ID</th>
                    <th style="width: 60px;" class="hidden-on-mobile">Parts</th>
                    <th style="width: 80px;" class="hidden-on-mobile">Cost</th>
                    <th style="width: 80px;" class="hidden-on-mobile">Attempts</th>
                    <th style="width: 150px;">Created</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logsData['items'] as $log): ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                    </th>
                    <td><?php echo esc_html($log->id); ?></td>
                    <td><?php echo get_status_badge($log->status, $status_colors); ?></td>
                    <td><?php echo get_reference_html($log); ?></td>
                    <td><code style="background:none;padding:0;"><?php echo esc_html($log->phone_masked); ?></code></td>
                    <td>
                        <?php 
                        if (!empty($log->message_excerpt)) {
                            echo esc_html(mb_strlen($log->message_excerpt) > 50 ? mb_substr($log->message_excerpt, 0, 50) . '...' : $log->message_excerpt);
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ($log->message_id): ?>
                            <div style="display:flex;align-items:center;gap:5px;">
                                <span title="<?php echo esc_attr($log->message_id); ?>" style="font-family:monospace; font-size:12px;">
                                    <?php echo esc_html(substr($log->message_id, 0, 8)) . '...'; ?>
                                </span>
                                <button type="button" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm copy-msg-id" data-clipboard="<?php echo esc_attr($log->message_id); ?>" title="Copy ID" style="padding: 0 4px; height: 24px;">
                                    <span class="dashicons dashicons-admin-page" style="font-size: 14px; width:14px; height:14px; margin: 0;"></span>
                                </button>
                            </div>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td class="hidden-on-mobile"><?php echo isset($log->parts_final) && $log->parts_final !== null ? esc_html((string)$log->parts_final) : '&mdash;'; ?></td>
                    <td class="hidden-on-mobile"><?php echo isset($log->cost_final) && $log->cost_final !== null ? esc_html((string)$log->cost_final) : '&mdash;'; ?></td>
                    <td class="hidden-on-mobile"><?php echo esc_html((string)$log->attempts); ?></td>
                    <td>
                        <?php 
                        $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at));
                        echo esc_html($date); 
                        ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: var(--ersaal-space-sm);">
                            <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm view-log-details" data-log='<?php echo esc_attr(json_encode($log)); ?>'>
                                <?php esc_html_e('Details', 'ersaal'); ?>
                            </button>
                            <button type="button" class="ersaal-btn ersaal-btn-danger ersaal-btn-sm ersaal-btn-ghost" onclick="if(confirm('<?php esc_attr_e('Are you sure you want to delete this log? This only deletes the local record.', 'ersaal'); ?>')) { document.getElementById('ersaal-single-delete-id').value = <?php echo esc_attr($log->id); ?>; document.getElementById('ersaal-single-delete-form').submit(); }">
                                <span class="dashicons dashicons-trash" style="margin:0; font-size:14px; width:14px; height:14px;"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e('Select All', 'ersaal'); ?></label>
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Message ID</th>
                    <th class="hidden-on-mobile">Parts</th>
                    <th class="hidden-on-mobile">Cost</th>
                    <th class="hidden-on-mobile">Attempts</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </tfoot>
        </table>

        <!-- Pagination -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'ersaal'); ?></label>
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php esc_html_e('Bulk actions', 'ersaal'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'ersaal'); ?></option>
                </select>
                <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e('Apply', 'ersaal'); ?>" name="ersaal_bulk_action" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete selected logs?', 'ersaal'); ?>');">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $logsData['total'], 'ersaal'), number_format_i18n($logsData['total'])); ?></span>
                
                <?php if ($logsData['pages'] > 1): 
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $logsData['pages'],
                        'current' => $logsData['page']
                    ]);
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                endif; ?>
            </div>
        </div>
        </form>
    <?php endif; ?>
</div>

<!-- Hidden form for single delete -->
<form method="post" id="ersaal-single-delete-form" style="display:none;">
    <?php wp_nonce_field('ersaal_delete_log'); ?>
    <input type="hidden" name="ersaal_delete_log" value="1">
    <input type="hidden" name="log_id" id="ersaal-single-delete-id" value="">
</form>

<!-- Details Modal -->
<div id="ersaal-log-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter: blur(2px);">
    <div class="ersaal-card" style="width: 600px; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column; padding: 0; overflow: hidden; box-shadow: var(--ersaal-shadow-lg);">
        <div style="padding: var(--ersaal-space-md) var(--ersaal-space-lg); border-bottom: 1px solid var(--ersaal-border-color); display: flex; justify-content: space-between; align-items: center; background: var(--ersaal-bg-surface-2);">
            <h2 class="ersaal-card-title" style="margin: 0; font-size: var(--ersaal-text-lg);"><?php esc_html_e('Log Details', 'ersaal'); ?></h2>
            <button type="button" id="ersaal-close-modal" class="ersaal-btn ersaal-btn-ghost" style="padding: 0; width: 32px; height: 32px; font-size: 24px; display: flex; align-items: center; justify-content: center; line-height: 1;">&times;</button>
        </div>
        <div style="padding: var(--ersaal-space-lg); overflow-y: auto;">
            <table class="form-table" role="presentation" style="margin: 0;">
                <tbody id="ersaal-log-modal-body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media screen and (max-width: 782px) {
    .hidden-on-mobile { display: none; }
    .ersaal-logs-wrap .tablenav-pages { text-align: left; margin-top: 10px; }
}
#ersaal-log-modal-body th { padding-top: 5px; padding-bottom: 5px; width: 30%; }
#ersaal-log-modal-body td { padding-top: 5px; padding-bottom: 5px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy ID functionality
    const copyBtns = document.querySelectorAll('.copy-msg-id');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-clipboard');
            navigator.clipboard.writeText(text).then(() => {
                const icon = this.querySelector('.dashicons');
                icon.classList.remove('dashicons-admin-page');
                icon.classList.add('dashicons-yes');
                icon.style.color = 'green';
                setTimeout(() => {
                    icon.classList.remove('dashicons-yes');
                    icon.classList.add('dashicons-admin-page');
                    icon.style.color = '';
                }, 2000);
            });
        });
    });

    // Modal functionality
    const modal = document.getElementById('ersaal-log-modal');
    const modalBody = document.getElementById('ersaal-log-modal-body');
    const closeBtn = document.getElementById('ersaal-close-modal');

    document.querySelectorAll('.view-log-details').forEach(btn => {
        btn.addEventListener('click', function() {
            const log = JSON.parse(this.getAttribute('data-log'));
            
            let html = '';
            const fields = [
                { key: 'id', label: 'Log ID' },
                { key: 'status', label: 'Status', format: val => val.toUpperCase() },
                { key: 'source', label: 'Source', format: val => val.toUpperCase() },
                { key: 'source_id', label: 'Source ID' },
                { key: 'source_event', label: 'Source Event' },
                { key: 'phone_masked', label: 'Masked Phone' },
                { key: 'message_id', label: 'Message ID' },
                { key: 'parts', label: 'Parts' },
                { key: 'cost', label: 'Cost' },
                { key: 'attempts', label: 'Attempts' },
                { key: 'api_http_code', label: 'HTTP Code' },
                { key: 'api_error', label: 'API Error', style: 'color:red;' },
                { key: 'created_at', label: 'Created At' },
                { key: 'updated_at', label: 'Updated At' },
            ];

            fields.forEach(f => {
                let val = log[f.key];
                if (val === null || val === undefined || val === '') {
                    val = '&mdash;';
                } else if (f.format) {
                    val = escapeHtml(String(f.format(val)));
                } else {
                    val = escapeHtml(String(val));
                }
                
                const style = f.style ? ` style="${f.style}"` : '';
                html += `<tr><th scope="row"><strong>${f.label}</strong></th><td${style}>${val}</td></tr>`;
            });
            
            let messageText = log['message_text'];
            if (messageText === null || messageText === undefined || messageText === '') {
                messageText = log['message_excerpt'];
            }
            if (messageText === null || messageText === undefined || messageText === '') {
                messageText = '&mdash;';
            } else {
                messageText = `<div style="background: var(--ersaal-bg-surface-2); padding: var(--ersaal-space-md); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-sm); white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: var(--ersaal-text-sm); line-height: 1.5; color: var(--ersaal-text-primary);">${escapeHtml(String(messageText))}</div>
                               <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm copy-msg-text" style="margin-top: var(--ersaal-space-sm);" data-clipboard="${escapeHtml(String(messageText))}">
                                   <span class="dashicons dashicons-admin-page" style="margin-right: 4px; font-size: 14px; width: 14px; height: 14px; display: inline-block;"></span>
                                   <?php esc_attr_e('Copy Message', 'ersaal'); ?>
                               </button>`;
            }
            html += `<tr><th scope="row"><strong>${escapeHtml('Message')}</strong></th><td>${messageText}</td></tr>`;

            modalBody.innerHTML = html;
            modal.style.display = 'flex';
            
            // Attach copy event for the new button
            const copyMsgBtn = modalBody.querySelector('.copy-msg-text');
            if (copyMsgBtn) {
                copyMsgBtn.addEventListener('click', function() {
                    const text = this.getAttribute('data-clipboard');
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<?php esc_attr_e('Copied!', 'ersaal'); ?>';
                        setTimeout(() => {
                            this.innerHTML = originalText;
                        }, 2000);
                    });
                });
            }
        });
    });

    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
