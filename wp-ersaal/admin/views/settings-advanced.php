<?php
if (!defined('ABSPATH')) {
    exit;
}
$clean_on_uninstall = get_option('ersaal_clean_on_uninstall', false);
?>
<form method="post" action="options.php">
    <?php settings_fields('ersaal_advanced_settings'); ?>
    
    <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
        <div class="ersaal-field">
            <h3 class="ersaal-label"><?php esc_html_e('Uninstall Behavior', 'ersaal'); ?></h3>
            <label for="ersaal_clean_on_uninstall" style="display: flex; align-items: flex-start; gap: var(--ersaal-space-sm); font-size: var(--ersaal-text-md);">
                <input name="ersaal_clean_on_uninstall" type="checkbox" id="ersaal_clean_on_uninstall" value="1" <?php checked($clean_on_uninstall, 1); ?> style="margin-top: 3px;" />
                <span>
                    <?php esc_html_e('Delete all data (Logs and Settings) when deleting the plugin.', 'ersaal'); ?>
                    <br>
                    <span class="ersaal-field-help" style="color: var(--ersaal-text-error); display: inline-block; margin-top: 4px;">
                        <?php esc_html_e('Warning: If checked, all your SMS logs will be permanently deleted upon plugin uninstallation.', 'ersaal'); ?>
                    </span>
                </span>
            </label>
        </div>
    </div>
    
    <div style="margin-top: var(--ersaal-space-xl);">
        <button type="submit" class="ersaal-btn ersaal-btn-primary">
            <?php esc_html_e('Save Settings', 'ersaal'); ?>
        </button>
    </div>
</form>
