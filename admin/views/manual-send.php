<?php
declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}

$subscriptionNames = [];
$hasSmsSubscription = false;
foreach ($status['subscriptions'] as $subscription) {
    $planName = (string) ($subscription['plan_name'] ?? '');
    if ($planName !== '') {
        $subscriptionNames[] = $planName;
    }
    if (stripos($planName, 'otp') === false) {
        $hasSmsSubscription = true;
    }
}
$subscriptionLabel = $subscriptionNames ? implode(', ', $subscriptionNames) : __('Not available', 'ersaal-sms-gateway');
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-medium">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('Send SMS', 'ersaal-sms-gateway'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Send one message through the connected Ersaal project.', 'ersaal-sms-gateway'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#send-sms')); ?>" class="ersaal-btn ersaal-btn-secondary">
                <?php echo ersaal_admin_icon('help'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php esc_html_e('Need help?', 'ersaal-sms-gateway'); ?>
            </a>
        </div>
    </header>

    <?php if (!$status['connected']): ?>
        <div class="ersaal-card">
            <div class="ersaal-alert ersaal-alert-danger" role="alert">
                <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Ersaal is not connected', 'ersaal-sms-gateway'); ?></p>
                <p><?php echo esc_html($status['error']); ?></p>
            </div>
            <div class="ersaal-send-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings')); ?>" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Open connection settings', 'ersaal-sms-gateway'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="ersaal-card">
            <div class="ersaal-send-readiness" role="status">
                <div class="ersaal-send-readiness-copy">
                    <span class="ersaal-status-line is-success">
                        <span class="ersaal-status-dot" aria-hidden="true"></span>
                        <?php esc_html_e('Ready to send', 'ersaal-sms-gateway'); ?>
                    </span>
                    <span><span class="ersaal-table-meta"><?php esc_html_e('Project', 'ersaal-sms-gateway'); ?></span> <strong><?php echo esc_html($status['project_name']); ?></strong></span>
                    <span><span class="ersaal-table-meta"><?php esc_html_e('Subscription', 'ersaal-sms-gateway'); ?></span> <strong><?php echo esc_html($subscriptionLabel); ?></strong></span>
                </div>
                <?php if ($status['sms_balance'] !== null): ?>
                    <span class="ersaal-badge <?php echo esc_attr(((float) $status['sms_balance'] > 0 || $hasSmsSubscription) ? 'ersaal-badge-info' : 'ersaal-badge-warning'); ?>">
                        <?php /* translators: %s: SMS balance amount */ ?>
                        <?php printf(esc_html__('SMS balance: %s', 'ersaal-sms-gateway'), esc_html((string) $status['sms_balance'])); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($status['sms_balance'] !== null && (float) $status['sms_balance'] <= 0 && !$hasSmsSubscription): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="note">
                    <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('No available SMS balance or subscription', 'ersaal-sms-gateway'); ?></p>
                    <p><?php esc_html_e('The API may reject this message until the project has an eligible SMS payment source.', 'ersaal-sms-gateway'); ?></p>
                </div>
            <?php endif; ?>

            <form id="ersaal-manual-send-form" method="post" class="ersaal-send-form">
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_phone"><?php esc_html_e('Recipient', 'ersaal-sms-gateway'); ?></label>
                    <input type="tel" id="ersaal_phone" name="phone" class="ersaal-input ersaal-ltr" required placeholder="0912345678" autocomplete="tel" />
                    <p class="ersaal-field-help"><?php esc_html_e('Libyana or Almadar: 091 / 092 / 093 / 094', 'ersaal-sms-gateway'); ?></p>
                </div>

                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_message"><?php esc_html_e('Message', 'ersaal-sms-gateway'); ?></label>
                    <textarea id="ersaal_message" name="message" rows="6" class="ersaal-textarea" required></textarea>
                    <div class="ersaal-message-meta" aria-live="polite">
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Characters', 'ersaal-sms-gateway'); ?><strong id="ersaal_char_count">0</strong></span>
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Encoding', 'ersaal-sms-gateway'); ?><strong id="ersaal_encoding">GSM-7</strong></span>
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Estimated parts', 'ersaal-sms-gateway'); ?><strong id="ersaal_parts_count">0</strong></span>
                    </div>
                </div>

                <details class="ersaal-disclosure">
                    <summary>
                        <span><?php esc_html_e('Payment and advanced options', 'ersaal-sms-gateway'); ?></span>
                        <?php echo ersaal_admin_icon('chevron'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </summary>
                    <div class="ersaal-disclosure-content ersaal-form-stack">
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_sender"><?php esc_html_e('Sender ID', 'ersaal-sms-gateway'); ?></label>
                            <input type="text" id="ersaal_sender" name="sender" class="ersaal-input ersaal-ltr" placeholder="Lamah" />
                            <p class="ersaal-field-help"><?php esc_html_e('Use an approved Sender ID for this project.', 'ersaal-sms-gateway'); ?></p>
                        </div>
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_payment_type"><?php esc_html_e('Payment type', 'ersaal-sms-gateway'); ?></label>
                            <select id="ersaal_payment_type" name="payment_type" class="ersaal-select">
                                <option value="wallet"><?php esc_html_e('Wallet', 'ersaal-sms-gateway'); ?></option>
                                <option value="subscription"><?php esc_html_e('Subscription', 'ersaal-sms-gateway'); ?></option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="ersaal-send-actions">
                    <span class="spinner ersaal-send-spinner" id="ersaal_spinner" aria-hidden="true"></span>
                    <button type="submit" id="ersaal_submit_btn" class="ersaal-btn ersaal-btn-primary ersaal-btn-prominent">
                        <?php echo ersaal_admin_icon('send'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php esc_html_e('Send SMS', 'ersaal-sms-gateway'); ?>
                    </button>
                </div>
            </form>

            <div id="ersaal_response_area" class="ersaal-alert border-l-4 p-4 mb-4 ersaal-send-result" role="status" aria-live="polite" hidden>
                <p class="ersaal-alert-title">
                    <span id="ersaal_response_success_icon"><?php echo ersaal_admin_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <span id="ersaal_response_error_icon" hidden><?php echo ersaal_admin_icon('warning'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <span id="ersaal_response_title"></span>
                </p>
                <p id="ersaal_response_message" hidden></p>
                <div id="ersaal_response_details" class="ersaal-result-grid">
                    <div id="ersaal_result_message_id_wrap">
                        <span class="ersaal-result-label"><?php esc_html_e('Message ID', 'ersaal-sms-gateway'); ?></span>
                        <span id="ersaal_result_message_id" class="ersaal-result-value ersaal-code ersaal-ltr"></span>
                    </div>
                    <div>
                        <span class="ersaal-result-label"><?php esc_html_e('Status', 'ersaal-sms-gateway'); ?></span>
                        <span class="ersaal-result-value"><?php esc_html_e('Accepted', 'ersaal-sms-gateway'); ?></span>
                    </div>
                    <div>
                        <span class="ersaal-result-label"><?php esc_html_e('Log ID', 'ersaal-sms-gateway'); ?></span>
                        <span id="ersaal_result_log_id" class="ersaal-result-value solid-code ersaal-ltr"></span>
                    </div>
                    <div id="ersaal_result_parts_wrap">
                        <span class="ersaal-result-label"><?php esc_html_e('Parts', 'ersaal-sms-gateway'); ?></span>
                        <span id="ersaal_result_parts" class="ersaal-result-value"></span>
                    </div>
                </div>
                <a id="ersaal_response_logs_link" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm">
                    <?php esc_html_e('View in logs', 'ersaal-sms-gateway'); ?>
                    <?php echo ersaal_admin_icon('arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
