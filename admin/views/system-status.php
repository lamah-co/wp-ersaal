<?php
declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="ersaal-settings-panel">
    <section class="ersaal-settings-section" aria-labelledby="ersaal-system-status-title">
        <header class="ersaal-settings-section-header">
            <h2 id="ersaal-system-status-title"><?php esc_html_e('Background processing', 'ersaal-sms-gateway'); ?></h2>
            <p><?php esc_html_e('Runtime services used to process queued and retried messages.', 'ersaal-sms-gateway'); ?></p>
        </header>

        <div class="ersaal-table-wrap">
            <table class="ersaal-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Component', 'ersaal-sms-gateway'); ?></th>
                        <th><?php esc_html_e('Status', 'ersaal-sms-gateway'); ?></th>
                        <th><?php esc_html_e('Notes', 'ersaal-sms-gateway'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ersaal-system-name">Action Scheduler</td>
                        <td>
                            <?php if ($status['action_scheduler']): ?>
                                <span class="ersaal-badge ersaal-badge-success"><?php esc_html_e('Active', 'ersaal-sms-gateway'); ?></span>
                            <?php else: ?>
                                <span class="ersaal-badge ersaal-badge-danger"><?php esc_html_e('Missing', 'ersaal-sms-gateway'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="ersaal-system-note"><?php esc_html_e('WooCommerce provides Action Scheduler. WP-Cron is used when it is unavailable.', 'ersaal-sms-gateway'); ?></td>
                    </tr>
                    <tr>
                        <td class="ersaal-system-name">WP-Cron</td>
                        <td>
                            <?php if ($status['wp_cron_disabled']): ?>
                                <span class="ersaal-badge ersaal-badge-warning"><?php esc_html_e('Disabled by constant', 'ersaal-sms-gateway'); ?></span>
                            <?php else: ?>
                                <span class="ersaal-badge ersaal-badge-success"><?php esc_html_e('Enabled', 'ersaal-sms-gateway'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="ersaal-system-note"><?php esc_html_e('If disabled, configure a server cron job to trigger wp-cron.php.', 'ersaal-sms-gateway'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
