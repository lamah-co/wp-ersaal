<?php
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
$subscriptionLabel = $subscriptionNames ? implode(', ', $subscriptionNames) : __('Not available', 'ersaal');
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-medium">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('Send SMS', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Send one message through the connected Ersaal project.', 'ersaal'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#send-sms')); ?>" class="ersaal-btn ersaal-btn-secondary">
                <?php echo ersaal_admin_icon('help'); ?>
                <?php esc_html_e('Need help?', 'ersaal'); ?>
            </a>
        </div>
    </header>

    <?php if (!$status['connected']): ?>
        <div class="ersaal-card">
            <div class="ersaal-alert ersaal-alert-danger" role="alert">
                <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); ?><?php esc_html_e('Ersaal is not connected', 'ersaal'); ?></p>
                <p><?php echo esc_html($status['error']); ?></p>
            </div>
            <div class="ersaal-send-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings')); ?>" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Open connection settings', 'ersaal'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="ersaal-card">
            <div class="ersaal-send-readiness" role="status">
                <div class="ersaal-send-readiness-copy">
                    <span class="ersaal-status-line is-success">
                        <span class="ersaal-status-dot" aria-hidden="true"></span>
                        <?php esc_html_e('Ready to send', 'ersaal'); ?>
                    </span>
                    <span><span class="ersaal-table-meta"><?php esc_html_e('Project', 'ersaal'); ?></span> <strong><?php echo esc_html($status['project_name']); ?></strong></span>
                    <span><span class="ersaal-table-meta"><?php esc_html_e('Subscription', 'ersaal'); ?></span> <strong><?php echo esc_html($subscriptionLabel); ?></strong></span>
                </div>
                <?php if ($status['sms_balance'] !== null): ?>
                    <span class="ersaal-badge <?php echo ((float) $status['sms_balance'] > 0 || $hasSmsSubscription) ? 'ersaal-badge-info' : 'ersaal-badge-warning'; ?>">
                        <?php printf(esc_html__('SMS balance: %s', 'ersaal'), esc_html((string) $status['sms_balance'])); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($status['sms_balance'] !== null && (float) $status['sms_balance'] <= 0 && !$hasSmsSubscription): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="note">
                    <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); ?><?php esc_html_e('No available SMS balance or subscription', 'ersaal'); ?></p>
                    <p><?php esc_html_e('The API may reject this message until the project has an eligible SMS payment source.', 'ersaal'); ?></p>
                </div>
            <?php endif; ?>

            <form id="ersaal-manual-send-form" method="post" class="ersaal-send-form">
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_phone"><?php esc_html_e('Recipient', 'ersaal'); ?></label>
                    <input type="tel" id="ersaal_phone" name="phone" class="ersaal-input ersaal-ltr" required placeholder="+21891XXXXXXX" autocomplete="tel" />
                    <p class="ersaal-field-help"><?php esc_html_e('Include the country code, for example +21891XXXXXXX.', 'ersaal'); ?></p>
                </div>

                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_message"><?php esc_html_e('Message', 'ersaal'); ?></label>
                    <textarea id="ersaal_message" name="message" rows="6" class="ersaal-textarea" required></textarea>
                    <div class="ersaal-message-meta" aria-live="polite">
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Characters', 'ersaal'); ?><strong id="ersaal_char_count">0</strong></span>
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Encoding', 'ersaal'); ?><strong id="ersaal_encoding">GSM-7</strong></span>
                        <span class="ersaal-message-meta-item"><?php esc_html_e('Estimated parts', 'ersaal'); ?><strong id="ersaal_parts_count">0</strong></span>
                    </div>
                </div>

                <details class="ersaal-disclosure">
                    <summary>
                        <span><?php esc_html_e('Payment and advanced options', 'ersaal'); ?></span>
                        <?php echo ersaal_admin_icon('chevron'); ?>
                    </summary>
                    <div class="ersaal-disclosure-content ersaal-form-stack">
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_sender"><?php esc_html_e('Sender ID', 'ersaal'); ?></label>
                            <input type="text" id="ersaal_sender" name="sender" class="ersaal-input ersaal-ltr" placeholder="Lamah" />
                            <p class="ersaal-field-help"><?php esc_html_e('Use an approved Sender ID for this project.', 'ersaal'); ?></p>
                        </div>
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_payment_type"><?php esc_html_e('Payment type', 'ersaal'); ?></label>
                            <select id="ersaal_payment_type" name="payment_type" class="ersaal-select">
                                <option value="wallet"><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                                <option value="subscription"><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="ersaal-send-actions">
                    <span class="spinner ersaal-send-spinner" id="ersaal_spinner" aria-hidden="true"></span>
                    <button type="submit" id="ersaal_submit_btn" class="ersaal-btn ersaal-btn-primary ersaal-btn-prominent">
                        <?php echo ersaal_admin_icon('send'); ?>
                        <?php esc_html_e('Send SMS', 'ersaal'); ?>
                    </button>
                </div>
            </form>

            <div id="ersaal_response_area" class="ersaal-alert ersaal-send-result" role="status" aria-live="polite" hidden>
                <p class="ersaal-alert-title">
                    <span id="ersaal_response_success_icon"><?php echo ersaal_admin_icon('check'); ?></span>
                    <span id="ersaal_response_error_icon" hidden><?php echo ersaal_admin_icon('warning'); ?></span>
                    <span id="ersaal_response_title"></span>
                </p>
                <p id="ersaal_response_message" hidden></p>
                <div id="ersaal_response_details" class="ersaal-result-grid">
                    <div id="ersaal_result_message_id_wrap">
                        <span class="ersaal-result-label"><?php esc_html_e('Message ID', 'ersaal'); ?></span>
                        <span id="ersaal_result_message_id" class="ersaal-result-value ersaal-code ersaal-ltr"></span>
                    </div>
                    <div>
                        <span class="ersaal-result-label"><?php esc_html_e('Status', 'ersaal'); ?></span>
                        <span class="ersaal-result-value"><?php esc_html_e('Accepted', 'ersaal'); ?></span>
                    </div>
                    <div>
                        <span class="ersaal-result-label"><?php esc_html_e('Log ID', 'ersaal'); ?></span>
                        <span id="ersaal_result_log_id" class="ersaal-result-value ersaal-code ersaal-ltr"></span>
                    </div>
                    <div id="ersaal_result_parts_wrap">
                        <span class="ersaal-result-label"><?php esc_html_e('Parts', 'ersaal'); ?></span>
                        <span id="ersaal_result_parts" class="ersaal-result-value"></span>
                    </div>
                </div>
                <a id="ersaal_response_logs_link" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm">
                    <?php esc_html_e('View in logs', 'ersaal'); ?>
                    <?php echo ersaal_admin_icon('arrow'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
