<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-manual-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Send SMS Message', 'ersaal'); ?></h1>
    <p><?php esc_html_e('Send a manual SMS message via the connected Ersaal project.', 'ersaal'); ?></p>
    <hr class="wp-header-end">
    
    <div class="ersaal-manual-container">
        <!-- Connection Status Card -->
        <div class="ersaal-card ersaal-status-card">
            <h2><?php esc_html_e('Connection Status', 'ersaal'); ?></h2>
            <?php if ($status['connected']): ?>
                <div class="ersaal-status-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php esc_html_e('Connected', 'ersaal'); ?></strong>
                </div>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Project', 'ersaal'); ?></th>
                        <td><?php echo esc_html($status['project_name']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Status', 'ersaal'); ?></th>
                        <td><?php echo esc_html(ucfirst($status['status'])); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Wallet Balance', 'ersaal'); ?></th>
                        <td>
                            <?php echo esc_html((string)$status['balance']); ?>
                            <?php if ($status['balance'] === '0' || $status['balance'] === 0): ?>
                                <p style="color: #d63638; font-weight: bold; margin-top: 5px;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('رصيد المحفظة الحالي لا يكفي لإرسال SMS.', 'ersaal'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Subscription', 'ersaal'); ?></th>
                        <td>
                            <?php
                            $has_sms_sub = false;
                            if (!empty($status['subscriptions'])): ?>
                                <?php foreach ($status['subscriptions'] as $sub): 
                                    $is_otp = stripos($sub['plan_name'] ?? '', 'otp') !== false;
                                    if (!$is_otp) $has_sms_sub = true;
                                ?>
                                    <div style="margin-bottom: 10px;">
                                        <strong><?php echo esc_html($sub['plan_name'] ?? 'Active'); ?></strong>
                                        <br/>
                                        <?php esc_html_e('Type:', 'ersaal'); ?> <?php echo $is_otp ? 'OTP' : 'SMS'; ?>
                                        <?php if (!empty($sub['expired_at'])): ?>
                                            <br/>
                                            <?php esc_html_e('Expires:', 'ersaal'); ?> <?php echo esc_html(substr($sub['expired_at'], 0, 10)); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <em><?php esc_html_e('No active subscriptions', 'ersaal'); ?></em>
                            <?php endif; ?>
                            
                            <hr style="margin: 10px 0;">
                            <?php if ($status['sms_balance'] > 0 || $has_sms_sub): ?>
                                <strong><?php esc_html_e('SMS Subscription Balance:', 'ersaal'); ?></strong> <?php echo esc_html((string)$status['sms_balance']); ?>
                            <?php else: ?>
                                <p style="color: #d63638; font-weight: bold; margin-top: 5px;">
                                    <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php esc_html_e('لا يوجد اشتراك SMS صالح لهذا المشروع.', 'ersaal'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            <?php else: ?>
                <div class="ersaal-status-error">
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php esc_html_e('Not Connected', 'ersaal'); ?></strong>
                    <p><?php echo esc_html($status['error']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($status['connected']): ?>
        <div class="ersaal-card ersaal-form-card">
            <h2><?php esc_html_e('Message Details', 'ersaal'); ?></h2>
            <form id="ersaal-manual-send-form" method="post">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ersaal_phone"><?php esc_html_e('Phone Number', 'ersaal'); ?></label></th>
                        <td>
                            <input type="tel" id="ersaal_phone" name="phone" value="" class="regular-text ltr" required placeholder="+21891XXXXXXX" dir="ltr" />
                            <p class="description"><?php esc_html_e('Include country code (e.g., +21891XXXXXXX).', 'ersaal'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ersaal_sender"><?php esc_html_e('Sender ID', 'ersaal'); ?></label></th>
                        <td>
                            <!-- Temporary text input as requested, since no endpoint was found for sender IDs -->
                            <input type="text" id="ersaal_sender" name="sender" value="" class="regular-text ltr" placeholder="Lamah" />
                            <p class="description">
                                <?php esc_html_e('Enter the approved Sender ID for your project.', 'ersaal'); ?>
                                <br/>
                                <em><?php esc_html_e('Note: A dynamic dropdown will be implemented in future phases once the API endpoint is available.', 'ersaal'); ?></em>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ersaal_payment_type"><?php esc_html_e('Payment Type', 'ersaal'); ?></label></th>
                        <td>
                            <select id="ersaal_payment_type" name="payment_type">
                                <option value="wallet"><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                                <option value="subscription"><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ersaal_message"><?php esc_html_e('Message', 'ersaal'); ?></label></th>
                        <td>
                            <textarea id="ersaal_message" name="message" rows="5" cols="50" class="large-text" required></textarea>
                            
                            <div class="ersaal-message-stats">
                                <span class="stat-item"><?php esc_html_e('Characters:', 'ersaal'); ?> <strong id="ersaal_char_count">0</strong></span>
                                <span class="stat-item"><?php esc_html_e('Encoding:', 'ersaal'); ?> <strong id="ersaal_encoding">GSM-7</strong></span>
                                <span class="stat-item"><?php esc_html_e('Estimated Parts:', 'ersaal'); ?> <strong id="ersaal_parts_count">0</strong></span>
                            </div>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" id="ersaal_submit_btn" class="button button-primary">
                        <?php esc_html_e('Send Message', 'ersaal'); ?>
                    </button>
                    <span class="spinner" id="ersaal_spinner"></span>
                </p>
                <div id="ersaal_response_area" class="ersaal-response-area" style="display: none;"></div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
