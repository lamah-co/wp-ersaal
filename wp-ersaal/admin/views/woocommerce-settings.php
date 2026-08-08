<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="options.php" style="display: flex; flex-direction: column; gap: var(--ersaal-space-xxl);">
    <?php
    settings_fields('ersaal_woocommerce_settings');
    do_settings_sections('ersaal_woocommerce_settings');
    
    $enabled = get_option('ersaal_wc_enable', false);
    $sender = get_option('ersaal_wc_sender_id', 'Lamah');
    $payment = get_option('ersaal_wc_payment_type', 'wallet');
    ?>

    <div>
        <h2 class="ersaal-card-title"><?php esc_html_e('General Settings', 'ersaal'); ?></h2>
        <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
            <div class="ersaal-field">
                <label style="display: flex; align-items: flex-start; gap: var(--ersaal-space-sm); font-size: var(--ersaal-text-md);">
                    <input type="checkbox" name="ersaal_wc_enable" value="1" <?php checked(1, $enabled); ?> style="margin-top: 3px;" />
                    <span>
                        <strong class="ersaal-text-primary"><?php esc_html_e('Enable WooCommerce SMS', 'ersaal'); ?></strong><br>
                        <span class="ersaal-field-help" style="margin-top: 4px; display: inline-block;"><?php esc_html_e('Enable SMS notifications for WooCommerce events', 'ersaal'); ?></span>
                    </span>
                </label>
            </div>
            
            <div class="ersaal-field">
                <label class="ersaal-label" for="ersaal_wc_sender_id"><?php esc_html_e('Sender ID', 'ersaal'); ?></label>
                <input type="text" id="ersaal_wc_sender_id" name="ersaal_wc_sender_id" value="<?php echo esc_attr($sender); ?>" class="ersaal-input" dir="ltr" />
            </div>
            
            <div class="ersaal-field">
                <label class="ersaal-label" for="ersaal_wc_payment_type"><?php esc_html_e('Payment Type', 'ersaal'); ?></label>
                <select id="ersaal_wc_payment_type" name="ersaal_wc_payment_type" class="ersaal-select">
                    <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                    <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                </select>
            </div>
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--ersaal-border-color); margin: 0;">
    
    <div>
        <div style="display:flex; justify-content: space-between; align-items:center; margin-block-end: var(--ersaal-space-lg);">
            <h2 class="ersaal-card-title" style="margin: 0;"><?php esc_html_e('Event Templates', 'ersaal'); ?></h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm">
                <span class="dashicons dashicons-editor-help"></span>
                <?php esc_html_e('Need help?', 'ersaal'); ?>
            </a>
        </div>
        
        <div class="ersaal-alert ersaal-alert-info" style="margin-block-end: var(--ersaal-space-xl);">
            <div style="font-weight: var(--ersaal-fw-semibold); margin-bottom: var(--ersaal-space-sm); color: var(--ersaal-text-primary);"><?php esc_html_e('Available Variables (Click to insert into selected template):', 'ersaal'); ?></div>
            <div id="ersaal-variables-list" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php 
                $vars = ['{customer_name}', '{order_number}', '{order_total}', '{order_status}', '{site_name}', '{billing_first_name}', '{billing_last_name}'];
                foreach($vars as $v) {
                    echo '<button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm ersaal-var-btn" data-var="' . esc_attr($v) . '" dir="ltr">' . esc_html($v) . '</button>';
                }
                ?>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: var(--ersaal-space-xl);">
            <?php
            $events = [
                'new_order' => __('New Order', 'ersaal'),
                'processing' => __('Processing', 'ersaal'),
                'completed' => __('Completed', 'ersaal'),
                'cancelled' => __('Cancelled', 'ersaal')
            ];

            foreach ($events as $event => $label):
                $event_enabled = get_option("ersaal_wc_event_{$event}_enable", false);
                $template = get_option("ersaal_wc_event_{$event}_template", '');
            ?>
            <div style="background: var(--ersaal-bg-surface); padding: var(--ersaal-space-lg); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-md);">
                <h3 style="margin-top: 0; margin-bottom: var(--ersaal-space-md); font-size: var(--ersaal-text-lg); color: var(--ersaal-text-primary);"><?php echo esc_html($label); ?></h3>
                
                <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
                    <label style="display: flex; align-items: center; gap: var(--ersaal-space-sm); font-size: var(--ersaal-text-md);">
                        <input type="checkbox" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" <?php checked(1, $event_enabled); ?> />
                        <span style="font-weight: var(--ersaal-fw-medium);"><?php printf(esc_html__('Enable SMS for %s', 'ersaal'), $label); ?></span>
                    </label>
                    
                    <div class="ersaal-field">
                        <label class="ersaal-label" for="ersaal_wc_event_<?php echo esc_attr($event); ?>_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label>
                        <textarea id="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="3" class="ersaal-textarea ersaal-template-input" data-preview="preview_<?php echo esc_attr($event); ?>"><?php echo esc_textarea($template); ?></textarea>
                        
                        <div style="margin-top: var(--ersaal-space-sm); background: var(--ersaal-bg-surface-2); padding: var(--ersaal-space-md); border-radius: var(--ersaal-radius-sm);">
                            <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary); margin-bottom: var(--ersaal-space-xs); font-size: var(--ersaal-text-sm);"><?php esc_html_e('Preview:', 'ersaal'); ?></div>
                            <div id="preview_<?php echo esc_attr($event); ?>" style="white-space: pre-wrap; font-size: var(--ersaal-text-md); color: var(--ersaal-text-primary);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <hr style="border: none; border-top: 1px solid var(--ersaal-border-color); margin: 0;">
    
    <div>
        <h2 class="ersaal-card-title"><?php esc_html_e('Admin Notifications', 'ersaal'); ?></h2>
        <?php
        $admin_enabled = get_option('ersaal_wc_admin_new_order_enable', false);
        $admin_phone = get_option('ersaal_wc_admin_phone', '');
        $admin_template = get_option('ersaal_wc_admin_new_order_template', 'New order #{order_number} from {customer_name}. Total: {order_total}');
        ?>
        <div style="background: var(--ersaal-bg-surface); padding: var(--ersaal-space-lg); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-md);">
            <h3 style="margin-top: 0; margin-bottom: var(--ersaal-space-md); font-size: var(--ersaal-text-lg); color: var(--ersaal-text-primary);"><?php esc_html_e('New Order Notification', 'ersaal'); ?></h3>
            
            <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
                <label style="display: flex; align-items: center; gap: var(--ersaal-space-sm); font-size: var(--ersaal-text-md);">
                    <input type="checkbox" name="ersaal_wc_admin_new_order_enable" value="1" <?php checked(1, $admin_enabled); ?> />
                    <span style="font-weight: var(--ersaal-fw-medium);"><?php esc_html_e('Send SMS to store admin on new order', 'ersaal'); ?></span>
                </label>
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_admin_phone"><?php esc_html_e('Admin Phone Number', 'ersaal'); ?></label>
                    <input type="tel" id="ersaal_wc_admin_phone" name="ersaal_wc_admin_phone" value="<?php echo esc_attr($admin_phone); ?>" class="ersaal-input" placeholder="e.g. 218911234567" dir="ltr" />
                    <p class="ersaal-field-help"><?php esc_html_e('The phone number to receive admin notifications.', 'ersaal'); ?></p>
                </div>
                
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_admin_new_order_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label>
                    <textarea id="ersaal_wc_admin_new_order_template" name="ersaal_wc_admin_new_order_template" rows="3" class="ersaal-textarea ersaal-template-input" data-preview="preview_admin_new_order"><?php echo esc_textarea($admin_template); ?></textarea>
                    
                    <div style="margin-top: var(--ersaal-space-sm); background: var(--ersaal-bg-surface-2); padding: var(--ersaal-space-md); border-radius: var(--ersaal-radius-sm);">
                        <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary); margin-bottom: var(--ersaal-space-xs); font-size: var(--ersaal-text-sm);"><?php esc_html_e('Preview:', 'ersaal'); ?></div>
                        <div id="preview_admin_new_order" style="white-space: pre-wrap; font-size: var(--ersaal-text-md); color: var(--ersaal-text-primary);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (function_exists('wc_get_order_statuses')): ?>
        <hr style="border: none; border-top: 1px solid var(--ersaal-border-color); margin: 0;">
        <div>
            <h2 class="ersaal-card-title"><?php esc_html_e('Additional Order Statuses', 'ersaal'); ?></h2>
            <div style="display: flex; flex-direction: column; gap: var(--ersaal-space-xl);">
            <?php
            $statuses = wc_get_order_statuses();
            foreach ($statuses as $slug => $label):
                $event = str_replace('wc-', '', $slug);
                if (in_array($event, ['processing', 'completed', 'cancelled'], true)) {
                    continue;
                }
                $enabled = get_option("ersaal_wc_event_{$event}_enable", false);
                $template = get_option("ersaal_wc_event_{$event}_template", '');
            ?>
            <div style="background: var(--ersaal-bg-surface); padding: var(--ersaal-space-lg); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-md);">
                <h3 style="margin-top: 0; margin-bottom: var(--ersaal-space-md); font-size: var(--ersaal-text-lg); color: var(--ersaal-text-primary);"><?php echo esc_html($label); ?></h3>
                
                <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
                    <label style="display: flex; align-items: center; gap: var(--ersaal-space-sm); font-size: var(--ersaal-text-md);">
                        <input type="checkbox" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" <?php checked(1, $enabled); ?> />
                        <span style="font-weight: var(--ersaal-fw-medium);"><?php printf(esc_html__('Send SMS on %s', 'ersaal'), esc_html($label)); ?></span>
                    </label>
                    
                    <div class="ersaal-field">
                        <label class="ersaal-label" for="ersaal_wc_event_<?php echo esc_attr($event); ?>_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label>
                        <textarea id="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="3" class="ersaal-textarea ersaal-template-input" data-preview="preview_<?php echo esc_attr($event); ?>"><?php echo esc_textarea($template); ?></textarea>
                        
                        <div style="margin-top: var(--ersaal-space-sm); background: var(--ersaal-bg-surface-2); padding: var(--ersaal-space-md); border-radius: var(--ersaal-radius-sm);">
                            <div style="font-weight: var(--ersaal-fw-semibold); color: var(--ersaal-text-secondary); margin-bottom: var(--ersaal-space-xs); font-size: var(--ersaal-text-sm);"><?php esc_html_e('Preview:', 'ersaal'); ?></div>
                            <div id="preview_<?php echo esc_attr($event); ?>" style="white-space: pre-wrap; font-size: var(--ersaal-text-md); color: var(--ersaal-text-primary);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div>
        <button type="submit" class="ersaal-btn ersaal-btn-primary">
            <?php esc_html_e('Save Settings', 'ersaal'); ?>
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mockData = {
        '{customer_name}': 'أحمد محمد',
        '{order_number}': '1042',
        '{order_total}': '150.00',
        '{order_status}': 'مكتمل',
        '{site_name}': 'متجري',
        '{billing_first_name}': 'أحمد',
        '{billing_last_name}': 'محمد'
    };

    function updatePreview(textarea) {
        const previewId = textarea.getAttribute('data-preview');
        const previewEl = document.getElementById(previewId);
        if (!previewEl) return;

        let text = textarea.value;
        for (const [key, val] of Object.entries(mockData)) {
            text = text.replaceAll(key, val);
        }
        previewEl.textContent = text;
        
        let isArabic = /[\u0600-\u06FF]/.test(text);
        let charLimit = isArabic ? 70 : 160;
        let nextLimit = isArabic ? 67 : 153;
        
        let length = text.length;
        let parts = 1;
        if (length > charLimit) {
            parts = Math.ceil(length / nextLimit);
        }
        
        let statsEl = previewEl.nextElementSibling;
        if (!statsEl || !statsEl.classList.contains('ersaal-template-stats')) {
            statsEl = document.createElement('div');
            statsEl.className = 'ersaal-template-stats';
            statsEl.style.marginTop = '10px';
            statsEl.style.fontSize = '12px';
            statsEl.style.color = '#666';
            previewEl.parentNode.appendChild(statsEl);
        }
        statsEl.innerHTML = `Characters: <strong>${length}</strong> | Estimated Parts: <strong>${parts}</strong> | Encoding: <strong>${isArabic ? 'Unicode' : 'GSM'}</strong>`;
    }

    let lastFocused = null;
    document.querySelectorAll('.ersaal-template-input').forEach(textarea => {
        textarea.addEventListener('input', () => updatePreview(textarea));
        textarea.addEventListener('focus', () => lastFocused = textarea);
        updatePreview(textarea);
    });

    document.querySelectorAll('.ersaal-var-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!lastFocused) {
                alert('<?php esc_attr_e('Please click inside a message template field first.', 'ersaal'); ?>');
                return;
            }
            const variable = this.getAttribute('data-var');
            const start = lastFocused.selectionStart;
            const end = lastFocused.selectionEnd;
            const text = lastFocused.value;
            lastFocused.value = text.substring(0, start) + variable + text.substring(end);
            lastFocused.selectionStart = lastFocused.selectionEnd = start + variable.length;
            lastFocused.focus();
            updatePreview(lastFocused);
        });
    });
});
</script>


