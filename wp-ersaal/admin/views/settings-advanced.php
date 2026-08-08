<?php
if (!defined('ABSPATH')) {
    exit;
}
$clean_on_uninstall = get_option('ersaal_clean_on_uninstall', false);
?>
<form method="post" action="options.php">
    <?php settings_fields('ersaal_advanced_settings'); ?>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Uninstall Behavior', 'ersaal'); ?></th>
            <td>
                <label for="ersaal_clean_on_uninstall">
                    <input name="ersaal_clean_on_uninstall" type="checkbox" id="ersaal_clean_on_uninstall" value="1" <?php checked($clean_on_uninstall, 1); ?> />
                    <?php esc_html_e('Delete all data (Logs and Settings) when deleting the plugin.', 'ersaal'); ?>
                </label>
                <p class="description" style="color:red;"><?php esc_html_e('Warning: If checked, all your SMS logs will be permanently deleted upon plugin uninstallation.', 'ersaal'); ?></p>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
