<?php
if (!defined('ABSPATH')) {
    exit;
}

$page_url = admin_url('admin.php?page=ersaal-logs');
$current_status = $args['status'] ?? 'all';
$current_source = $args['source'] ?? 'all';
$search_query = $args['search'] ?? '';

// Badges colors
$status_colors = [
    'processing' => ['#e5f5fa', '#00a0d2'],
    'accepted' => ['#e6ffed', '#22863a'],
    'retry_scheduled' => ['#fff3e0', '#ff9800'],
    'failed' => ['#ffebe9', '#cb2431'],
    'error' => ['#ffebe9', '#cb2431'],
];

function get_status_badge(string $status, array $colors): string {
    $c = $colors[$status] ?? ['#f1f1f1', '#444'];
    return sprintf('<span style="background: %s; color: %s; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;">%s</span>', esc_attr($c[0]), esc_attr($c[1]), esc_html($status));
}

function get_reference_html(object $log): string {
    if ($log->source === 'woocommerce' && !empty($log->source_id)) {
        if (function_exists('get_edit_post_link')) {
            $link = get_edit_post_link((int)$log->source_id);
            if ($link) {
                return sprintf('<a href="%s" style="font-weight:bold;">Order #%s</a>', esc_url($link), esc_html($log->source_id));
            }
        }
        return sprintf('<strong>Order #%s</strong>', esc_html($log->source_id));
    }
    return esc_html(ucfirst($log->source));
}
?>

<div class="wrap ersaal-logs-wrap">
    <div style="display:flex; align-items:center;">
        <h1 class="wp-heading-inline"><?php esc_html_e('SMS Logs', 'ersaal'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#logs')); ?>" style="margin-left: 15px; font-size: 14px; font-weight: normal; text-decoration: none;"><span class="dashicons dashicons-editor-help" style="font-size: 16px; margin-top: 3px;"></span> <?php esc_html_e('Need help?', 'ersaal'); ?></a>
    </div>
    <p><?php esc_html_e('Track SMS requests sent through Ersaal.', 'ersaal'); ?></p>

    <?php settings_errors('ersaal_logs'); ?>

    <!-- Summary Cards -->
    <div style="display: flex; gap: 15px; margin: 20px 0;">
        <?php
        $cards = [
            ['label' => 'Total', 'key' => 'total', 'color' => '#444'],
            ['label' => 'Accepted', 'key' => 'accepted', 'color' => '#22863a'],
            ['label' => 'Failed/Error', 'keys' => ['failed', 'error'], 'color' => '#cb2431'],
            ['label' => 'Retry', 'key' => 'retry_scheduled', 'color' => '#ff9800'],
            ['label' => 'Processing', 'key' => 'processing', 'color' => '#00a0d2'],
        ];
        
        foreach ($cards as $card): 
            $count = 0;
            if (isset($card['keys'])) {
                foreach ($card['keys'] as $k) { $count += $stats[$k] ?? 0; }
            } else {
                $count = $stats[$card['key']] ?? 0;
            }
        ?>
            <div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid <?php echo esc_attr($card['color']); ?>; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <div style="font-size: 13px; color: #666; font-weight: 600; text-transform: uppercase;"><?php echo esc_html($card['label']); ?></div>
                <div style="font-size: 24px; font-weight: 300; margin-top: 5px; color: #222;"><?php echo number_format_i18n($count); ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters & Search -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <form method="get" action="admin.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="ersaal-logs">
            
            <select name="status">
                <option value="all" <?php selected($current_status, 'all'); ?>><?php esc_html_e('All Statuses', 'ersaal'); ?></option>
                <option value="processing" <?php selected($current_status, 'processing'); ?>><?php esc_html_e('Processing', 'ersaal'); ?></option>
                <option value="accepted" <?php selected($current_status, 'accepted'); ?>><?php esc_html_e('Accepted', 'ersaal'); ?></option>
                <option value="retry_scheduled" <?php selected($current_status, 'retry_scheduled'); ?>><?php esc_html_e('Retry Scheduled', 'ersaal'); ?></option>
                <option value="failed" <?php selected($current_status, 'failed'); ?>><?php esc_html_e('Failed', 'ersaal'); ?></option>
                <option value="error" <?php selected($current_status, 'error'); ?>><?php esc_html_e('Error', 'ersaal'); ?></option>
            </select>
            
            <select name="source">
                <option value="all" <?php selected($current_source, 'all'); ?>><?php esc_html_e('All Sources', 'ersaal'); ?></option>
                <option value="manual" <?php selected($current_source, 'manual'); ?>><?php esc_html_e('Manual', 'ersaal'); ?></option>
                <option value="woocommerce" <?php selected($current_source, 'woocommerce'); ?>><?php esc_html_e('WooCommerce', 'ersaal'); ?></option>
            </select>

            <?php if (!empty($events)): ?>
            <select name="event">
                <option value="all" <?php selected($_GET['event'] ?? 'all', 'all'); ?>><?php esc_html_e('All Events', 'ersaal'); ?></option>
                <?php foreach ($events as $evt): 
                    $label = ucwords(str_replace('_', ' ', $evt));
                ?>
                <option value="<?php echo esc_attr($evt); ?>" <?php selected($_GET['event'] ?? '', $evt); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <input type="date" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? ''); ?>" placeholder="<?php esc_attr_e('From Date', 'ersaal'); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? ''); ?>" placeholder="<?php esc_attr_e('To Date', 'ersaal'); ?>">
            
            <?php submit_button(__('Filter', 'ersaal'), 'secondary', '', false); ?>
            <?php if (isset($_GET['status']) || isset($_GET['source']) || isset($_GET['event']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['s'])): ?>
                <a href="<?php echo esc_url($page_url); ?>" class="button"><?php esc_html_e('Clear Filters', 'ersaal'); ?></a>
            <?php endif; ?>
        </form>
        
        <form method="get" action="admin.php" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="page" value="ersaal-logs">
            <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
            <input type="hidden" name="source" value="<?php echo esc_attr($current_source); ?>">
            <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('ID, Msg ID, Order, Phone', 'ersaal'); ?>" style="width: 250px;">
            <?php submit_button(__('Search Logs', 'ersaal'), 'secondary', '', false); ?>
        </form>

        <form method="get" action="admin.php">
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
            <?php submit_button(__('Export CSV', 'ersaal'), 'secondary', '', false); ?>
        </form>
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
        <form method="post" id="ersaal-logs-form">
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
                                <button type="button" class="button button-small copy-msg-id" data-clipboard="<?php echo esc_attr($log->message_id); ?>" title="Copy ID">
                                    <span class="dashicons dashicons-admin-page" style="font-size: 14px; width:14px; height:14px; margin-top: 2px;"></span>
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
                        <div style="display: flex; gap: 5px;">
                            <button type="button" class="button view-log-details" data-log='<?php echo esc_attr(json_encode($log)); ?>'>
                                <?php esc_html_e('Details', 'ersaal'); ?>
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this log? This only deletes the local record.', 'ersaal'); ?>');">
                                <?php wp_nonce_field('ersaal_delete_log'); ?>
                                <input type="hidden" name="log_id" value="<?php echo esc_attr($log->id); ?>">
                                <button type="submit" name="ersaal_delete_log" class="button" style="color: #a00;"><?php esc_html_e('Delete', 'ersaal'); ?></button>
                            </form>
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

<!-- Details Modal -->
<div id="ersaal-log-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; width: 600px; max-width: 90%; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); max-height: 90vh; display: flex; flex-direction: column;">
        <div style="padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background: #fcfcfc;">
            <h2 style="margin: 0; font-size: 18px;"><?php esc_html_e('Log Details', 'ersaal'); ?></h2>
            <button type="button" id="ersaal-close-modal" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #666;">&times;</button>
        </div>
        <div style="padding: 20px; overflow-y: auto;">
            <table class="form-table" role="presentation">
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
                messageText = `<div style="background: #f9f9f9; padding: 10px; border: 1px solid #e2e4e7; border-radius: 4px; white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 13px; line-height: 1.5;">${escapeHtml(String(messageText))}</div>
                               <button type="button" class="button button-small copy-msg-text" style="margin-top: 5px;" data-clipboard="${escapeHtml(String(messageText))}">
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
