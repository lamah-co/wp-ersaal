<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
$cleanOnUninstall = get_option('ersaal_clean_on_uninstall', false);
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
    </div>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save settings', 'ersaal'); ?></button>
    </div>
</form>
