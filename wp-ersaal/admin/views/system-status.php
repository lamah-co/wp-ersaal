<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array $status */
?>
<table class="wp-list-table widefat fixed striped ersaal-table" style="border: none; box-shadow: none;">
    <thead>
        <tr>
            <th><?php esc_html_e('Component', 'ersaal'); ?></th>
            <th style="width: 150px;"><?php esc_html_e('Status', 'ersaal'); ?></th>
            <th><?php esc_html_e('Notes', 'ersaal'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: var(--ersaal-fw-medium); color: var(--ersaal-text-primary);">Action Scheduler</td>
            <td>
                <?php if ($status['action_scheduler']): ?>
                    <span class="ersaal-badge ersaal-badge-success"><?php esc_html_e('Active', 'ersaal'); ?></span>
                <?php else: ?>
                    <span class="ersaal-badge ersaal-badge-danger"><?php esc_html_e('Missing', 'ersaal'); ?></span>
                <?php endif; ?>
            </td>
            <td style="color: var(--ersaal-text-secondary);"><?php esc_html_e('WooCommerce includes Action Scheduler. Without it, WP-Cron will be used.', 'ersaal'); ?></td>
        </tr>
        <tr>
            <td style="font-weight: var(--ersaal-fw-medium); color: var(--ersaal-text-primary);">WP-Cron</td>
            <td>
                <?php if ($status['wp_cron_disabled']): ?>
                    <span class="ersaal-badge ersaal-badge-warning"><?php esc_html_e('Disabled via Constant', 'ersaal'); ?></span>
                <?php else: ?>
                    <span class="ersaal-badge ersaal-badge-success"><?php esc_html_e('Enabled', 'ersaal'); ?></span>
                <?php endif; ?>
            </td>
            <td style="color: var(--ersaal-text-secondary);">
                <?php esc_html_e('If WP-Cron is disabled, ensure you have a server-level cron job configured to trigger wp-cron.php.', 'ersaal'); ?>
            </td>
        </tr>
    </tbody>
</table>
