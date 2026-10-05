<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}
$otpEnabled = (bool) $options->get('otp_enabled', false);
$loginEnabled = (bool) $options->get('otp_login_enabled', false);
$emergencyDisabled = defined('ERSAAL_DISABLE_LOGIN_OTP') && ERSAAL_DISABLE_LOGIN_OTP;
?>
<form method="post" action="options.php" id="ersaal-otp-settings-form">
    <?php settings_fields('ersaal_otp_settings'); ?>

    <div class="ersaal-settings-panel">
        <section class="ersaal-settings-section" aria-labelledby="ersaal-otp-feature-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-otp-feature-title"><?php esc_html_e('One-time passwords', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Use Ersaal to send and verify short-lived codes. OTP stays off until you enable it.', 'ersaal'); ?></p>
            </header>

            <label class="ersaal-switch-row" for="ersaal_otp_enabled">
                <span class="ersaal-switch-copy">
                    <span class="ersaal-switch-title"><?php esc_html_e('Enable OTP services', 'ersaal'); ?></span>
                    <span class="ersaal-switch-description"><?php esc_html_e('Allows OTP tests, user phone verification, and developer integrations.', 'ersaal'); ?></span>
                </span>
                <span class="ersaal-switch-control">
                    <input name="ersaal_otp_enabled" type="checkbox" id="ersaal_otp_enabled" value="1" <?php checked($otpEnabled); ?> />
                    <span class="ersaal-switch-track" aria-hidden="true"></span>
                </span>
            </label>
        </section>

        <section class="ersaal-settings-section" aria-labelledby="ersaal-otp-service-status-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-otp-service-status-title"><?php esc_html_e('OTP service status', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Live project and OTP capacity values reported by the connected Ersaal API.', 'ersaal'); ?></p>
            </header>
            <dl class="ersaal-summary-list">
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('Connection', 'ersaal'); ?></dt>
                    <dd><span class="ersaal-badge <?php echo $otpStatus['connected'] ? 'ersaal-badge-success' : 'ersaal-badge-danger'; ?>"><?php echo esc_html($otpStatus['connected'] ? __('Connected', 'ersaal') : __('Unavailable', 'ersaal')); ?></span></dd>
                </div>
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('OTP balance', 'ersaal'); ?></dt>
                    <dd><?php echo $otpStatus['otp_balance'] !== null ? esc_html(number_format_i18n((float) $otpStatus['otp_balance'])) : esc_html__('Not reported', 'ersaal'); ?></dd>
                </div>
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('OTP limit', 'ersaal'); ?></dt>
                    <dd><?php echo $otpStatus['otp_limit'] !== null ? esc_html(number_format_i18n((float) $otpStatus['otp_limit'])) : esc_html__('Not reported', 'ersaal'); ?></dd>
                </div>
            </dl>
            <?php if (!$otpStatus['connected']): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="status"><p><?php esc_html_e('OTP service availability could not be confirmed. Review Connection settings before enabling a login challenge.', 'ersaal'); ?></p></div>
            <?php elseif ($options->get('otp_payment_type', 'wallet') === 'subscription' && $otpStatus['otp_balance'] !== null && (float) $otpStatus['otp_balance'] <= 0): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="status"><p><?php esc_html_e('The API reports no available OTP subscription units. Choose wallet payment or update the Ersaal subscription.', 'ersaal'); ?></p></div>
            <?php endif; ?>
        </section>

        <section class="ersaal-settings-section" id="ersaal-otp-configuration" aria-labelledby="ersaal-otp-config-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-otp-config-title"><?php esc_html_e('OTP defaults', 'ersaal'); ?></h2>
                <p><?php esc_html_e('These values are sent to Ersaal when a new verification code is requested.', 'ersaal'); ?></p>
            </header>

            <div class="ersaal-form-stack">
                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_otp_sender"><?php esc_html_e('Sender name', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Use an active, approved sender from the connected Ersaal project.', 'ersaal'); ?></p>
                    </div>
                    <input name="ersaal_otp_sender" type="text" id="ersaal_otp_sender" class="ersaal-input" value="<?php echo esc_attr((string) $options->get('otp_sender', '')); ?>" autocomplete="off" />
                </div>

                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_otp_payment_type"><?php esc_html_e('Payment source', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Choose the wallet or the OTP units in your subscription.', 'ersaal'); ?></p>
                    </div>
                    <select name="ersaal_otp_payment_type" id="ersaal_otp_payment_type" class="ersaal-select">
                        <option value="wallet" <?php selected($options->get('otp_payment_type', 'wallet'), 'wallet'); ?>><?php esc_html_e('Wallet balance', 'ersaal'); ?></option>
                        <option value="subscription" <?php selected($options->get('otp_payment_type', 'wallet'), 'subscription'); ?>><?php esc_html_e('OTP subscription', 'ersaal'); ?></option>
                    </select>
                </div>

                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_otp_length"><?php esc_html_e('Code length', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Ersaal supports four- or six-digit codes.', 'ersaal'); ?></p>
                    </div>
                    <select name="ersaal_otp_length" id="ersaal_otp_length" class="ersaal-select">
                        <option value="4" <?php selected((int) $options->get('otp_length', 6), 4); ?>><?php esc_html_e('4 digits', 'ersaal'); ?></option>
                        <option value="6" <?php selected((int) $options->get('otp_length', 6), 6); ?>><?php esc_html_e('6 digits', 'ersaal'); ?></option>
                    </select>
                </div>

                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_otp_expiration"><?php esc_html_e('Code lifetime', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Expired and successfully used codes cannot be verified again.', 'ersaal'); ?></p>
                    </div>
                    <select name="ersaal_otp_expiration" id="ersaal_otp_expiration" class="ersaal-select">
                        <?php for ($minute = 1; $minute <= 10; $minute++): ?>
                            <option value="<?php echo esc_attr((string) $minute); ?>" <?php selected((int) $options->get('otp_expiration', 5), $minute); ?>><?php
                                /* translators: %s: number of minutes */
                                printf(esc_html(_n('%s minute', '%s minutes', $minute, 'ersaal')), esc_html(number_format_i18n($minute)));
                            ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="ersaal-form-row">
                    <div class="ersaal-form-row-copy">
                        <label class="ersaal-label" for="ersaal_otp_language"><?php esc_html_e('Message language', 'ersaal'); ?></label>
                        <p><?php esc_html_e('Automatic follows the current WordPress locale.', 'ersaal'); ?></p>
                    </div>
                    <select name="ersaal_otp_language" id="ersaal_otp_language" class="ersaal-select">
                        <option value="auto" <?php selected($options->get('otp_language', 'auto'), 'auto'); ?>><?php esc_html_e('Automatic', 'ersaal'); ?></option>
                        <option value="ar" <?php selected($options->get('otp_language', 'auto'), 'ar'); ?>><?php esc_html_e('Arabic', 'ersaal'); ?></option>
                        <option value="en" <?php selected($options->get('otp_language', 'auto'), 'en'); ?>><?php esc_html_e('English', 'ersaal'); ?></option>
                    </select>
                </div>
            </div>
        </section>

        <section class="ersaal-settings-section" aria-labelledby="ersaal-login-otp-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-login-otp-title"><?php esc_html_e('WordPress login verification', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Adds a second step after a user enters a valid WordPress username and password.', 'ersaal'); ?></p>
            </header>

            <?php if ($emergencyDisabled): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="status">
                    <p class="ersaal-alert-title"><?php esc_html_e('Login OTP is disabled by wp-config.php', 'ersaal'); ?></p>
                    <p><?php esc_html_e('Remove ERSAAL_DISABLE_LOGIN_OTP or set it to false after resolving the emergency.', 'ersaal'); ?></p>
                </div>
            <?php endif; ?>

            <label class="ersaal-switch-row" for="ersaal_otp_login_enabled">
                <span class="ersaal-switch-copy">
                    <span class="ersaal-switch-title"><?php esc_html_e('Allow OTP for WordPress login', 'ersaal'); ?></span>
                    <span class="ersaal-switch-description"><?php esc_html_e('Only users with a verified phone who opt in are challenged. Existing users remain unchanged by default.', 'ersaal'); ?></span>
                </span>
                <span class="ersaal-switch-control">
                    <input name="ersaal_otp_login_enabled" type="checkbox" id="ersaal_otp_login_enabled" value="1" <?php checked($loginEnabled); ?> <?php disabled($emergencyDisabled); ?> />
                    <span class="ersaal-switch-track" aria-hidden="true"></span>
                </span>
            </label>
        </section>

        <section class="ersaal-settings-section" aria-labelledby="ersaal-otp-tools-title">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-otp-tools-title"><?php esc_html_e('Validate the setup', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Save these settings, then run a real send-and-verify test before enabling login verification for users.', 'ersaal'); ?></p>
            </header>
            <div class="ersaal-inline-actions">
                <a class="ersaal-btn ersaal-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-otp-test')); ?>"><?php esc_html_e('Open OTP Test', 'ersaal'); ?></a>
                <a class="ersaal-btn ersaal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-otp-logs')); ?>"><?php esc_html_e('View OTP activity', 'ersaal'); ?></a>
            </div>
        </section>
    </div>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save OTP settings', 'ersaal'); ?></button>
    </div>
</form>
