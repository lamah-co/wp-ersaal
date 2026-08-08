<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-admin ersaal-page">
    <div class="ersaal-page-header">
        <div>
            <h1 class="ersaal-page-title"><?php esc_html_e('Ersaal SMS Overview', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Monitor your SMS activity and system performance.', 'ersaal'); ?></p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-manual-send')); ?>" class="ersaal-btn ersaal-btn-primary">
                <span class="dashicons dashicons-email-alt"></span>
                <?php esc_html_e('Send SMS', 'ersaal'); ?>
            </a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--ersaal-grid-gap); margin-block-end: var(--ersaal-space-xxl);">
        <!-- Card 1 -->
        <div class="ersaal-card-stat">
            <h3 class="ersaal-card-title" style="margin-top: 0; color: var(--ersaal-text-secondary); font-size: var(--ersaal-text-sm); font-weight: var(--ersaal-fw-medium);"><?php esc_html_e('Messages Today', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: var(--ersaal-fw-bold); color: var(--ersaal-text-primary);"><?php echo esc_html(number_format_i18n($stats['total_today'])); ?></div>
        </div>
        
        <!-- Card 2 -->
        <div class="ersaal-card-stat">
            <h3 class="ersaal-card-title" style="margin-top: 0; color: var(--ersaal-text-secondary); font-size: var(--ersaal-text-sm); font-weight: var(--ersaal-fw-medium);"><?php esc_html_e('Accepted', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: var(--ersaal-fw-bold); color: var(--ersaal-color-success);"><?php echo esc_html(number_format_i18n($stats['accepted_today'])); ?></div>
        </div>
        
        <!-- Card 3 -->
        <div class="ersaal-card-stat">
            <h3 class="ersaal-card-title" style="margin-top: 0; color: var(--ersaal-text-secondary); font-size: var(--ersaal-text-sm); font-weight: var(--ersaal-fw-medium);"><?php esc_html_e('Failed / Error', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: var(--ersaal-fw-bold); color: var(--ersaal-color-danger);"><?php echo esc_html(number_format_i18n($stats['failed_today'])); ?></div>
        </div>
        
        <!-- Card 4 -->
        <div class="ersaal-card-stat">
            <h3 class="ersaal-card-title" style="margin-top: 0; color: var(--ersaal-text-secondary); font-size: var(--ersaal-text-sm); font-weight: var(--ersaal-fw-medium);"><?php esc_html_e('Processing / Retry', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: var(--ersaal-fw-bold); color: var(--ersaal-color-warning);"><?php echo esc_html(number_format_i18n($stats['retry_today'])); ?></div>
        </div>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-block-end: var(--ersaal-space-md);">
        <h2 class="ersaal-card-title" style="margin: 0; font-size: var(--ersaal-text-xl);"><?php esc_html_e('Recent Messages', 'ersaal'); ?></h2>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm">
            <?php esc_html_e('View All Logs', 'ersaal'); ?>
        </a>
    </div>

    <div class="ersaal-card" style="padding: 0; overflow-x: auto;">
        <?php if (empty($recent['items'])): ?>
            <div class="ersaal-empty-state">
                <p class="ersaal-empty-state-title"><?php esc_html_e('No SMS activity yet.', 'ersaal'); ?></p>
                <p class="ersaal-empty-state-text"><?php esc_html_e('Messages sent through Ersaal will appear here.', 'ersaal'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-manual-send')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm"><?php esc_html_e('Send your first SMS', 'ersaal'); ?></a>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped ersaal-table" style="border: none; box-shadow: none;">
                <thead>
                    <tr>
                        <th style="width: 120px;"><?php esc_html_e('Status', 'ersaal'); ?></th>
                        <th style="width: 140px;"><?php esc_html_e('Phone', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Message', 'ersaal'); ?></th>
                        <th style="width: 150px;"><?php esc_html_e('Created', 'ersaal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $status_badges = [
                        'accepted' => 'ersaal-badge-success',
                        'delivered' => 'ersaal-badge-success',
                        'sent' => 'ersaal-badge-success',
                        'processing' => 'ersaal-badge-info',
                        'pending' => 'ersaal-badge-info',
                        'error' => 'ersaal-badge-danger',
                        'failed' => 'ersaal-badge-danger',
                        'rejected' => 'ersaal-badge-danger',
                        'retry_scheduled' => 'ersaal-badge-warning',
                        'queued' => 'ersaal-badge-warning'
                    ];
                    
                    foreach ($recent['items'] as $log): 
                        $badge_class = $status_badges[$log->status] ?? 'ersaal-badge-muted';
                    ?>
                    <tr>
                        <td>
                            <span class="ersaal-badge <?php echo esc_attr($badge_class); ?>">
                                <?php echo esc_html(strtoupper($log->status)); ?>
                            </span>
                        </td>
                        <td><code style="background:none;padding:0; direction: ltr; unicode-bidi: isolate; display: inline-block; font-size: var(--ersaal-text-md);"><?php echo esc_html($log->phone_masked); ?></code></td>
                        <td style="color: var(--ersaal-text-secondary);">
                            <?php 
                            if (!empty($log->message_excerpt)) {
                                echo esc_html(mb_strlen($log->message_excerpt) > 60 ? mb_substr($log->message_excerpt, 0, 60) . '...' : $log->message_excerpt);
                            } else {
                                echo '&mdash;';
                            }
                            ?>
                        </td>
                        <td>
                            <span style="direction: ltr; unicode-bidi: isolate; display: inline-block; color: var(--ersaal-text-muted); font-size: var(--ersaal-text-sm);">
                                <?php 
                                $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at));
                                echo esc_html($date); 
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
