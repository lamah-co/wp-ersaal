<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-medium ersaal-otp-test-page">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('OTP Test', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Send a real verification code and confirm the complete OTP flow.', 'ersaal'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <a class="ersaal-btn ersaal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings&tab=otp')); ?>"><?php esc_html_e('OTP settings', 'ersaal'); ?></a>
        </div>
    </header>

    <div class="ersaal-alert ersaal-alert-info" role="note">
        <p class="ersaal-alert-title"><?php esc_html_e('This uses your real Ersaal account', 'ersaal'); ?></p>
        <p><?php esc_html_e('A successful send can consume wallet balance or an OTP subscription unit. Verification codes are never written to WordPress logs.', 'ersaal'); ?></p>
    </div>

    <section class="ersaal-card ersaal-otp-workflow" aria-labelledby="ersaal-otp-test-title">
        <div class="ersaal-otp-card-header">
            <div>
                <h2 id="ersaal-otp-test-title"><?php esc_html_e('Verify a phone number', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Complete both steps to confirm the sender, billing source, and verification endpoint.', 'ersaal'); ?></p>
            </div>
            <span class="ersaal-badge ersaal-badge-muted" id="ersaal-otp-state"><?php esc_html_e('Ready', 'ersaal'); ?></span>
        </div>

        <ol class="ersaal-otp-steps" aria-label="<?php esc_attr_e('OTP test steps', 'ersaal'); ?>">
            <li class="is-active" data-otp-step-indicator="send"><span>1</span><?php esc_html_e('Send', 'ersaal'); ?></li>
            <li data-otp-step-indicator="verify"><span>2</span><?php esc_html_e('Verify', 'ersaal'); ?></li>
            <li data-otp-step-indicator="done"><span>3</span><?php esc_html_e('Complete', 'ersaal'); ?></li>
        </ol>

        <div id="ersaal-otp-feedback" class="ersaal-alert" role="status" aria-live="polite" hidden></div>

        <form id="ersaal-otp-send-form" class="ersaal-form-stack" data-otp-panel="send">
            <div class="ersaal-field">
                <label class="ersaal-label" for="ersaal-otp-test-phone"><?php esc_html_e('Phone number', 'ersaal'); ?></label>
                <input type="tel" id="ersaal-otp-test-phone" class="ersaal-input ersaal-ltr" inputmode="tel" autocomplete="tel" placeholder="0912345678" required />
                <p class="ersaal-field-help">
                    <?php esc_html_e('Libyana or Almadar: 091 / 092 / 093 / 094', 'ersaal'); ?><br>
                    <?php esc_html_e('The activity log keeps only a masked version and a one-way fingerprint.', 'ersaal'); ?>
                </p>
            </div>
            <div class="ersaal-inline-actions">
                <button type="submit" class="ersaal-btn ersaal-btn-primary" id="ersaal-otp-send-button"><?php esc_html_e('Send verification code', 'ersaal'); ?></button>
                <span class="spinner" id="ersaal-otp-send-spinner" aria-hidden="true"></span>
            </div>
        </form>

        <form id="ersaal-otp-verify-form" class="ersaal-form-stack" data-otp-panel="verify" hidden>
            <div class="ersaal-field">
                <label class="ersaal-label" for="ersaal-otp-test-code"><?php esc_html_e('Verification code', 'ersaal'); ?></label>
                <input type="text" id="ersaal-otp-test-code" class="ersaal-input ersaal-ltr ersaal-otp-code-input" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="one-time-code" required />
                <p class="ersaal-field-help" id="ersaal-otp-countdown"></p>
            </div>
            <div class="ersaal-inline-actions">
                <button type="submit" class="ersaal-btn ersaal-btn-primary" id="ersaal-otp-verify-button"><?php esc_html_e('Verify code', 'ersaal'); ?></button>
                <button type="button" class="ersaal-btn ersaal-btn-secondary" id="ersaal-otp-resend-button" disabled><?php esc_html_e('Send a new code', 'ersaal'); ?></button>
                <span class="spinner" id="ersaal-otp-verify-spinner" aria-hidden="true"></span>
            </div>
        </form>

        <div id="ersaal-otp-complete" class="ersaal-empty-state" data-otp-panel="done" hidden>
            <span aria-hidden="true"><?php echo ersaal_admin_icon('check'); ?></span>
            <h3 class="ersaal-empty-state-title"><?php esc_html_e('OTP flow verified', 'ersaal'); ?></h3>
            <p class="ersaal-empty-state-text"><?php esc_html_e('The code was accepted once and the local test state was removed.', 'ersaal'); ?></p>
            <button type="button" class="ersaal-btn ersaal-btn-secondary" id="ersaal-otp-start-over"><?php esc_html_e('Run another test', 'ersaal'); ?></button>
        </div>
    </section>
</div>
