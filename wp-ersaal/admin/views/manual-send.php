<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-normal">
    <div class="ersaal-page-header">
        <div>
            <h1 class="ersaal-page-title"><?php esc_html_e('Send SMS Message', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Send a manual SMS message via the connected Ersaal project.', 'ersaal'); ?></p>
        </div>
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#send-sms')); ?>" class="ersaal-btn ersaal-btn-ghost">
                <span class="dashicons dashicons-editor-help"></span>
                <?php esc_html_e('Need help?', 'ersaal'); ?>
            </a>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr; gap: var(--ersaal-space-xl);">
        <!-- Connection Status Card -->
        <div class="ersaal-card">
            <h2 class="ersaal-card-title"><?php esc_html_e('Connection Status', 'ersaal'); ?></h2>
            <?php if ($status['connected']): ?>
                <div style="display: flex; align-items: center; gap: var(--ersaal-space-sm); color: var(--ersaal-color-success); margin-block-end: var(--ersaal-space-lg); font-weight: var(--ersaal-fw-medium);">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php esc_html_e('Connected', 'ersaal'); ?></strong>
                </div>
                <div style="display: grid; grid-template-columns: minmax(150px, auto) 1fr; gap: var(--ersaal-space-md); font-size: var(--ersaal-text-md);">
                    <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary);"><?php esc_html_e('Project', 'ersaal'); ?></div>
                    <div><?php echo esc_html($status['project_name']); ?></div>
                    
                    <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary);"><?php esc_html_e('Status', 'ersaal'); ?></div>
                    <div><?php echo esc_html(ucfirst($status['status'])); ?></div>
                    
                    <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary);"><?php esc_html_e('Wallet Balance', 'ersaal'); ?></div>
                    <div>
                        <?php echo esc_html((string)$status['balance']); ?>
                        <?php if ($status['balance'] === '0' || $status['balance'] === 0): ?>
                            <p style="color: var(--ersaal-color-danger); font-weight: var(--ersaal-fw-bold); margin-top: var(--ersaal-space-xs); display: flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                <?php esc_html_e('رصيد المحفظة الحالي لا يكفي لإرسال SMS.', 'ersaal'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary);"><?php esc_html_e('Subscription', 'ersaal'); ?></div>
                    <div>
                        <?php
                        $has_sms_sub = false;
                        if (!empty($status['subscriptions'])): ?>
                            <?php foreach ($status['subscriptions'] as $sub): 
                                $is_otp = stripos($sub['plan_name'] ?? '', 'otp') !== false;
                                if (!$is_otp) $has_sms_sub = true;
                            ?>
                                <div style="margin-bottom: var(--ersaal-space-sm);">
                                    <strong><?php echo esc_html($sub['plan_name'] ?? 'Active'); ?></strong>
                                    <br/>
                                    <span style="color: var(--ersaal-text-muted);"><?php esc_html_e('Type:', 'ersaal'); ?> <?php echo $is_otp ? 'OTP' : 'SMS'; ?></span>
                                    <?php if (!empty($sub['expired_at'])): ?>
                                        <br/>
                                        <span style="color: var(--ersaal-text-muted);"><?php esc_html_e('Expires:', 'ersaal'); ?> <?php echo esc_html(substr($sub['expired_at'], 0, 10)); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <em style="color: var(--ersaal-text-muted);"><?php esc_html_e('No active subscriptions', 'ersaal'); ?></em>
                        <?php endif; ?>
                        
                        <div style="border-top: 1px solid var(--ersaal-border-color); margin-block: var(--ersaal-space-md); padding-block-start: var(--ersaal-space-md);">
                            <?php if ($status['sms_balance'] > 0 || $has_sms_sub): ?>
                                <strong><?php esc_html_e('SMS Subscription Balance:', 'ersaal'); ?></strong> <?php echo esc_html((string)$status['sms_balance']); ?>
                            <?php else: ?>
                                <p style="color: var(--ersaal-color-danger); font-weight: var(--ersaal-fw-bold); margin: 0; display: flex; align-items: center; gap: 4px;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('لا يوجد اشتراك SMS صالح لهذا المشروع.', 'ersaal'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="ersaal-alert ersaal-alert-danger" style="margin-top: var(--ersaal-space-md);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-warning"></span>
                        <strong><?php esc_html_e('Not Connected', 'ersaal'); ?></strong>
                    </div>
                    <p style="margin-top: 8px; margin-bottom: 0;"><?php echo esc_html($status['error']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($status['connected']): ?>
        <div class="ersaal-card">
            <h2 class="ersaal-card-title"><?php esc_html_e('Message Details', 'ersaal'); ?></h2>
            <form id="ersaal-manual-send-form" method="post" style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_phone"><?php esc_html_e('Phone Number', 'ersaal'); ?></label>
                    <input type="tel" id="ersaal_phone" name="phone" value="" class="ersaal-input" required placeholder="+21891XXXXXXX" dir="ltr" />
                    <p class="ersaal-field-help"><?php esc_html_e('Include country code (e.g., +21891XXXXXXX).', 'ersaal'); ?></p>
                </div>
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_sender"><?php esc_html_e('Sender ID', 'ersaal'); ?></label>
                    <input type="text" id="ersaal_sender" name="sender" value="" class="ersaal-input" placeholder="Lamah" dir="ltr" />
                    <p class="ersaal-field-help">
                        <?php esc_html_e('Enter the approved Sender ID for your project.', 'ersaal'); ?>
                        <br/>
                        <em><?php esc_html_e('Note: A dynamic dropdown will be implemented in future phases once the API endpoint is available.', 'ersaal'); ?></em>
                    </p>
                </div>
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_payment_type"><?php esc_html_e('Payment Type', 'ersaal'); ?></label>
                    <select id="ersaal_payment_type" name="payment_type" class="ersaal-select">
                        <option value="wallet"><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                        <option value="subscription"><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                    </select>
                </div>
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_message"><?php esc_html_e('Message', 'ersaal'); ?></label>
                    <textarea id="ersaal_message" name="message" rows="5" class="ersaal-textarea" required></textarea>
                    
                    <div style="margin-top: var(--ersaal-space-sm); padding: var(--ersaal-space-md); background: var(--ersaal-bg-surface-2); border-radius: var(--ersaal-radius-md); display: flex; gap: var(--ersaal-space-xl); font-size: var(--ersaal-text-sm); flex-wrap: wrap;">
                        <span><?php esc_html_e('Characters:', 'ersaal'); ?> <strong id="ersaal_char_count">0</strong></span>
                        <span><?php esc_html_e('Encoding:', 'ersaal'); ?> <strong id="ersaal_encoding">GSM-7</strong></span>
                        <span><?php esc_html_e('Estimated Parts:', 'ersaal'); ?> <strong id="ersaal_parts_count">0</strong></span>
                    </div>
                </div>
                
                <div style="margin-top: var(--ersaal-space-md); display: flex; align-items: center; gap: var(--ersaal-space-md);">
                    <button type="submit" id="ersaal_submit_btn" class="ersaal-btn ersaal-btn-primary">
                        <?php esc_html_e('Send Message', 'ersaal'); ?>
                    </button>
                    <span class="spinner" id="ersaal_spinner" style="float: none; margin: 0;"></span>
                </div>
                
                <div id="ersaal_response_area" class="ersaal-alert" style="display: none; margin-top: var(--ersaal-space-md);"></div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
