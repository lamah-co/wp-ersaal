<?php
declare(strict_types=1);
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
$cleanOnUninstall = get_option('ersaal_clean_on_uninstall', false);
$retentionDays    = (int) get_option('ersaal_log_retention_days', 0);
$nextCleanup      = wp_next_scheduled('ersaal_daily_log_cleanup');
$commonPresets    = [0, 15, 30, 60, 90, 180, 365];
$isCustom         = ($retentionDays > 0 && !in_array($retentionDays, $commonPresets, true));
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
                <p><?php esc_html_e('Automatically delete old SMS log entries to prevent database bloat.', 'ersaal'); ?></p>
            </header>

            <div class="ersaal-form-stack">
                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_log_retention_preset"><?php esc_html_e('Retention period', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Specify how long SMS logs should be kept before automatic deletion.', 'ersaal'); ?></p>
                    </div>
                    <div class="ersaal-field">
                        <select id="ersaal_log_retention_preset" class="ersaal-select">
                            <option value="0" <?php selected($retentionDays, 0); ?>><?php esc_html_e('Disabled (keep all logs)', 'ersaal'); ?></option>
                            <option value="15" <?php selected($retentionDays, 15); ?>><?php esc_html_e('15 days', 'ersaal'); ?></option>
                            <option value="30" <?php selected($retentionDays, 30); ?>><?php esc_html_e('30 days (1 month)', 'ersaal'); ?></option>
                            <option value="60" <?php selected($retentionDays, 60); ?>><?php esc_html_e('60 days (2 months)', 'ersaal'); ?></option>
                            <option value="90" <?php selected($retentionDays, 90); ?>><?php esc_html_e('90 days (3 months)', 'ersaal'); ?></option>
                            <option value="180" <?php selected($retentionDays, 180); ?>><?php esc_html_e('180 days (6 months)', 'ersaal'); ?></option>
                            <option value="365" <?php selected($retentionDays, 365); ?>><?php esc_html_e('365 days (1 year)', 'ersaal'); ?></option>
                            <option value="custom" <?php selected($isCustom, true); ?>><?php esc_html_e('Custom period...', 'ersaal'); ?></option>
                        </select>

                        <div id="ersaal_retention_custom_wrap" style="<?php echo $isCustom ? 'margin-block-start: var(--ersaal-space-sm);' : 'display: none; margin-block-start: var(--ersaal-space-sm);'; ?>">
                            <input
                                type="number"
                                id="ersaal_log_retention_days"
                                name="ersaal_log_retention_days"
                                value="<?php echo esc_attr((string) $retentionDays); ?>"
                                min="0"
                                max="3650"
                                step="1"
                                class="ersaal-input ersaal-ltr"
                                style="max-width: 160px;"
                                placeholder="<?php esc_attr_e('Number of days', 'ersaal'); ?>"
                            />
                        </div>

                        <p class="ersaal-field-help">
                            <?php if ($retentionDays > 0): ?>
                                <?php
                                if ($nextCleanup) {
                                    printf(
                                        /* translators: %s: human-readable time until next cleanup */
                                        esc_html__('Cleanup runs daily via WP-Cron. Next run in %s.', 'ersaal'),
                                        esc_html(human_time_diff((int) $nextCleanup))
                                    );
                                } else {
                                    esc_html_e('Scheduled to run daily via WP-Cron.', 'ersaal');
                                }
                                ?>
                            <?php else: ?>
                                <?php esc_html_e('Automatic cleanup is disabled. Logs will be kept indefinitely.', 'ersaal'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="ersaal-alert ersaal-alert-info" role="note">
                <p class="ersaal-alert-title">
                    <?php echo ersaal_admin_icon('logs'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php esc_html_e('Automated background cleanup', 'ersaal'); ?>
                </p>
                <p><?php esc_html_e('The cleanup operation runs silently in the background once every 24 hours without affecting site visitors or SMS delivery.', 'ersaal'); ?></p>
            </div>
        </section>
    </div>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save settings', 'ersaal'); ?></button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var preset = document.getElementById('ersaal_log_retention_preset');
    var input = document.getElementById('ersaal_log_retention_days');
    var customWrap = document.getElementById('ersaal_retention_custom_wrap');
    if (!preset || !input || !customWrap) return;

    preset.addEventListener('change', function () {
        if (this.value === 'custom') {
            customWrap.style.display = 'block';
            input.focus();
        } else {
            customWrap.style.display = 'none';
            input.value = this.value;
        }
    });

    input.addEventListener('input', function () {
        var val = parseInt(this.value, 10);
        var known = ['0', '15', '30', '60', '90', '180', '365'];
        if (known.indexOf(String(val)) !== -1) {
            preset.value = String(val);
        } else {
            preset.value = 'custom';
        }
    });
});
</script>
