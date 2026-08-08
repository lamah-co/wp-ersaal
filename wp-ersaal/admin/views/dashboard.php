<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-dashboard-wrap">
    <h1><?php esc_html_e('Ersaal SMS Dashboard', 'ersaal'); ?></h1>
    
    <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
        <!-- Card 1 -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; flex: 1; min-width: 200px; border-radius: 4px; border-left: 4px solid #00a0d2;">
            <h3 style="margin-top: 0; color: #555; font-size: 14px;"><?php esc_html_e('Messages Today', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #222;"><?php echo esc_html(number_format_i18n($stats['total_today'])); ?></div>
        </div>
        
        <!-- Card 2 -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; flex: 1; min-width: 200px; border-radius: 4px; border-left: 4px solid #46b450;">
            <h3 style="margin-top: 0; color: #555; font-size: 14px;"><?php esc_html_e('Accepted', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #46b450;"><?php echo esc_html(number_format_i18n($stats['accepted_today'])); ?></div>
        </div>
        
        <!-- Card 3 -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; flex: 1; min-width: 200px; border-radius: 4px; border-left: 4px solid #dc3232;">
            <h3 style="margin-top: 0; color: #555; font-size: 14px;"><?php esc_html_e('Failed / Error', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #dc3232;"><?php echo esc_html(number_format_i18n($stats['failed_today'])); ?></div>
        </div>
        
        <!-- Card 4 -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; flex: 1; min-width: 200px; border-radius: 4px; border-left: 4px solid #ffb900;">
            <h3 style="margin-top: 0; color: #555; font-size: 14px;"><?php esc_html_e('Processing / Retry', 'ersaal'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #ffb900;"><?php echo esc_html(number_format_i18n($stats['retry_today'])); ?></div>
        </div>
    </div>
    
    <h2 style="margin-top: 30px;"><?php esc_html_e('Recent Messages', 'ersaal'); ?></h2>
    <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 140px;">Phone</th>
                    <th>Message</th>
                    <th style="width: 150px;">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent['items'])): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                        <?php esc_html_e('No messages found.', 'ersaal'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $status_colors = [
                        'accepted' => 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;',
                        'processing' => 'background:#cce5ff;color:#004085;border:1px solid #b8daff;',
                        'error' => 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
                        'failed' => 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
                        'retry_scheduled' => 'background:#fff3cd;color:#856404;border:1px solid #ffeeba;'
                    ];
                    
                    foreach ($recent['items'] as $log): 
                        $color = $status_colors[$log->status] ?? 'background:#e2e3e5;color:#383d41;border:1px solid #d6d8db;';
                    ?>
                    <tr>
                        <td>
                            <span style="display:inline-block; padding:3px 8px; border-radius:3px; font-size:12px; font-weight:500; <?php echo $color; ?>">
                                <?php echo esc_html(strtoupper($log->status)); ?>
                            </span>
                        </td>
                        <td><code style="background:none;padding:0;"><?php echo esc_html($log->phone_masked); ?></code></td>
                        <td>
                            <?php 
                            if (!empty($log->message_excerpt)) {
                                echo esc_html(mb_strlen($log->message_excerpt) > 60 ? mb_substr($log->message_excerpt, 0, 60) . '...' : $log->message_excerpt);
                            } else {
                                echo '&mdash;';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at));
                            echo esc_html($date); 
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 15px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="button button-secondary">
            <?php esc_html_e('View All Logs', 'ersaal'); ?>
        </a>
    </div>
</div>
