<?php
declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
$isKeyDefined = defined('ERSAAL_API_KEY');
$apiUrl = $this->options->get('api_url', 'https://api.ersaal.com/');
$hasApiKey = !empty($this->options->get('api_key', ''));
?>
<form method="post" action="options.php">
    <?php settings_fields('ersaal_general_settings'); ?>

    <div class="ersaal-settings-panel">
        <section class="ersaal-settings-section" aria-labelledby="ersaal-connection-settings-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-connection-settings-title"><?php esc_html_e('Connection settings', 'ersaal-sms-gateway'); ?></h2>
                <p><?php esc_html_e('Connect this WordPress site to the intended Ersaal project.', 'ersaal-sms-gateway'); ?></p>
            </header>

            <div class="ersaal-form-stack">
                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_api_url"><?php esc_html_e('API base URL', 'ersaal-sms-gateway'); ?></label>
                        <p><?php esc_html_e('The Ersaal Gateway endpoint used for API requests.', 'ersaal-sms-gateway'); ?></p>
                    </div>
                    <input name="ersaal_api_url" type="url" id="ersaal_api_url" value="<?php echo esc_attr($apiUrl); ?>" class="ersaal-input ersaal-ltr" required />
                </div>

                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_api_key"><?php esc_html_e('API key', 'ersaal-sms-gateway'); ?></label>
                        <p><?php esc_html_e('Bearer token for the connected Ersaal project.', 'ersaal-sms-gateway'); ?></p>
                    </div>
                    <div class="ersaal-field">
                        <?php if ($isKeyDefined): ?>
                            <input id="ersaal_api_key" type="password" value="********" class="ersaal-input ersaal-ltr" disabled />
                            <p class="ersaal-field-help"><?php esc_html_e('Defined in wp-config.php and cannot be changed here.', 'ersaal-sms-gateway'); ?></p>
                        <?php else: ?>
                            <input name="ersaal_api_key" type="password" id="ersaal_api_key" value="<?php echo esc_attr($hasApiKey ? '********' : ''); ?>" class="ersaal-input ersaal-ltr" placeholder="<?php esc_attr_e('Enter your Bearer Token', 'ersaal-sms-gateway'); ?>" autocomplete="off" />
                            <?php if ($hasApiKey): ?>
                                <p class="ersaal-field-help"><?php esc_html_e('The API key is set. Keep ******** to retain the existing key.', 'ersaal-sms-gateway'); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="ersaal-settings-section" aria-labelledby="ersaal-connection-test-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-connection-test-title"><?php esc_html_e('Connection test', 'ersaal-sms-gateway'); ?></h2>
                <p><?php esc_html_e('Verify the currently saved credentials before configuring messaging.', 'ersaal-sms-gateway'); ?></p>
            </header>
            <div class="ersaal-inline-actions">
                <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-prominent" id="ersaal-test-api-btn"><?php echo ersaal_admin_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Test connection', 'ersaal-sms-gateway'); ?></button>
                <span class="spinner" id="ersaal-test-api-spinner" aria-hidden="true"></span>
            </div>
            <div id="ersaal-test-api-result" class="ersaal-alert ersaal-test-result" role="status" aria-live="polite" hidden></div>
        </section>
    </div>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save settings', 'ersaal-sms-gateway'); ?></button>
    </div>
</form>

