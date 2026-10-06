<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
$cleanOnUninstall  = get_option('ersaal_clean_on_uninstall', false);
$retentionDays     = (int) get_option('ersaal_log_retention_days', 0);
$nextCleanup       = wp_next_scheduled('ersaal_daily_log_cleanup');
?>
<form method="post" action="options.php">
    <?php settings_fields('ersaal_advanced_settings'); ?>

    <div class="ersaal-settings-panel">
        <section class="ersaal-settings-section" aria-labelledby="ersaal-uninstall-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-uninstall-title"><?php esc_html_e('Uninstall behavior', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Choose whether local Ersaal data should remain after the plugin is deleted.', 'ersaal'); ?></p>
            </header>

            <label class="ersaal-switch-row" for="ersaal_clean_on_uninstall">
                <span class="ersaal-switch-copy">
                    <span class="ersaal-switch-title"><?php esc_html_e('Delete all local data on uninstall', 'ersaal'); ?></span>
                    <span class="ersaal-switch-description"><?php esc_html_e('Removes saved settings and SMS logs when the plugin is deleted.', 'ersaal'); ?></span>
                </span>
                <span class="ersaal-switch-control">
                    <input name="ersaal_clean_on_uninstall" type="checkbox" id="ersaal_clean_on_uninstall" value="1" <?php checked($cleanOnUninstall, 1); ?> />
                    <span class="ersaal-switch-track" aria-hidden="true"></span>
                </span>
            </label>

            <div class="ersaal-alert ersaal-alert-warning" role="note">
                <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Permanent deletion', 'ersaal'); ?></p>
                <p><?php esc_html_e('When enabled, the local logs and settings cannot be recovered after uninstalling the plugin.', 'ersaal'); ?></p>
            </div>
        </section>

        <section class="ersaal-settings-section" aria-labelledby="ersaal-retention-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-retention-title"><?php esc_html_e('Log retention', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Automatically delete old SMS log entries to prevent the database table from growing indefinitely.', 'ersaal'); ?></p>
            </header>

            <div class="ersaal-field-row">
                <label class="ersaal-field-label" for="ersaal_log_retention_days">
                    <?php esc_html_e('Delete logs older than (days)', 'ersaal'); ?>
                </label>
                <input
                    type="number"
                    id="ersaal_log_retention_days"
                    name="ersaal_log_retention_days"
                    value="<?php echo esc_attr((string) $retentionDays); ?>"
                    min="0"
                    max="3650"
                    step="1"
                    class="ersaal-input-short"
                    aria-describedby="ersaal-retention-hint"
                />
                <p id="ersaal-retention-hint" class="ersaal-field-hint">
                    <?php esc_html_e('Set to 0 to disable automatic cleanup. The cleanup runs once per day via WP-Cron.', 'ersaal'); ?>
                    <?php if ($nextCleanup): ?>
                        <?php
                        /* translators: %s: human-readable time until next cleanup */
                        printf(
                            esc_html__('Next scheduled cleanup: %s.', 'ersaal'),
                            esc_html(human_time_diff((int) $nextCleanup))
                        );
                        ?>
                    <?php endif; ?>
                </p>
            </div>
        </section>
    </div>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save settings', 'ersaal'); ?></button>
    </div>
</form>
