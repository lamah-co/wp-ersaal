<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array $status */
?>
<table class="widefat striped">
    <thead>
        <tr>
            <th><?php esc_html_e('Component', 'ersaal'); ?></th>
            <th><?php esc_html_e('Status', 'ersaal'); ?></th>
            <th><?php esc_html_e('Notes', 'ersaal'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Action Scheduler</strong></td>
            <td>
                <?php if ($status['action_scheduler']): ?>
                    <span style="color:green; font-weight:bold;">✔ <?php esc_html_e('Active', 'ersaal'); ?></span>
                <?php else: ?>
                    <span style="color:red; font-weight:bold;">✘ <?php esc_html_e('Missing', 'ersaal'); ?></span>
                <?php endif; ?>
            </td>
            <td><?php esc_html_e('WooCommerce includes Action Scheduler. Without it, WP-Cron will be used.', 'ersaal'); ?></td>
        </tr>
        <tr>
            <td><strong>WP-Cron</strong></td>
            <td>
                <?php if ($status['wp_cron_disabled']): ?>
                    <span style="color:orange; font-weight:bold;">⚠ <?php esc_html_e('Disabled via Constant', 'ersaal'); ?></span>
                <?php else: ?>
                    <span style="color:green; font-weight:bold;">✔ <?php esc_html_e('Enabled', 'ersaal'); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php esc_html_e('If WP-Cron is disabled, ensure you have a server-level cron job configured to trigger wp-cron.php.', 'ersaal'); ?>
            </td>
        </tr>
    </tbody>
</table>
