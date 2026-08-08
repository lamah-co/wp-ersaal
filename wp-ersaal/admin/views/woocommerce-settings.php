<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="options.php">
    <?php
    settings_fields('ersaal_woocommerce_settings');
    do_settings_sections('ersaal_woocommerce_settings');
    
    $enabled = get_option('ersaal_wc_enable', false);
    $sender = get_option('ersaal_wc_sender_id', 'Lamah');
    $payment = get_option('ersaal_wc_payment_type', 'wallet');
    ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Enable WooCommerce SMS', 'ersaal'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="ersaal_wc_enable" value="1" <?php checked(1, $enabled); ?> />
                    <?php esc_html_e('Enable SMS notifications for WooCommerce events', 'ersaal'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ersaal_wc_sender_id"><?php esc_html_e('Sender ID', 'ersaal'); ?></label></th>
            <td>
                <input type="text" id="ersaal_wc_sender_id" name="ersaal_wc_sender_id" value="<?php echo esc_attr($sender); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ersaal_wc_payment_type"><?php esc_html_e('Payment Type', 'ersaal'); ?></label></th>
            <td>
                <select id="ersaal_wc_payment_type" name="ersaal_wc_payment_type">
                    <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                    <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                </select>
            </td>
        </tr>
    </table>

    <hr>
    <div style="display:flex; align-items:center;">
        <h2><?php esc_html_e('Event Templates', 'ersaal'); ?></h2>
        <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" style="margin-left: 15px; font-size: 14px; font-weight: normal; text-decoration: none;"><span class="dashicons dashicons-editor-help" style="font-size: 16px; margin-top: 3px;"></span> <?php esc_html_e('Need help?', 'ersaal'); ?></a>
    </div>
    
    <div style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-left: 4px solid #00a0d2;">
        <h4 style="margin-top: 0;"><?php esc_html_e('Available Variables (Click to insert into selected template):', 'ersaal'); ?></h4>
        <div id="ersaal-variables-list" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php 
            $vars = ['{customer_name}', '{order_number}', '{order_total}', '{order_status}', '{site_name}', '{billing_first_name}', '{billing_last_name}'];
            foreach($vars as $v) {
                echo '<button type="button" class="button ersaal-var-btn" data-var="' . esc_attr($v) . '">' . esc_html($v) . '</button>';
            }
            ?>
        </div>
    </div>

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
    <div class="ersaal-card" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background: #fafafa;">
        <h3><?php echo esc_html($label); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable', 'ersaal'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" <?php checked(1, $event_enabled); ?> />
                        <?php printf(esc_html__('Enable SMS for %s', 'ersaal'), $label); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ersaal_wc_event_<?php echo esc_attr($event); ?>_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label></th>
                <td>
                    <textarea id="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="3" class="large-text ersaal-template-input" data-preview="preview_<?php echo esc_attr($event); ?>"><?php echo esc_textarea($template); ?></textarea>
                    
                    <div style="margin-top: 10px; background: #e5f5fa; padding: 10px; border-left: 4px solid #00a0d2;">
                        <strong><?php esc_html_e('Preview:', 'ersaal'); ?></strong><br/>
                        <span id="preview_<?php echo esc_attr($event); ?>" style="white-space: pre-wrap;"></span>
                    </div>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>

    <hr>
    <h2><?php esc_html_e('Admin Notifications', 'ersaal'); ?></h2>
    <?php
    $admin_enabled = get_option('ersaal_wc_admin_new_order_enable', false);
    $admin_phone = get_option('ersaal_wc_admin_phone', '');
    $admin_template = get_option('ersaal_wc_admin_new_order_template', 'New order #{order_number} from {customer_name}. Total: {order_total}');
    ?>
    <div class="ersaal-card" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background: #fafafa;">
        <h3><?php esc_html_e('New Order Notification', 'ersaal'); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Enable', 'ersaal'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="ersaal_wc_admin_new_order_enable" value="1" <?php checked(1, $admin_enabled); ?> />
                        <?php esc_html_e('Send SMS to store admin on new order', 'ersaal'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ersaal_wc_admin_phone"><?php esc_html_e('Admin Phone Number', 'ersaal'); ?></label></th>
                <td>
                    <input type="text" id="ersaal_wc_admin_phone" name="ersaal_wc_admin_phone" value="<?php echo esc_attr($admin_phone); ?>" class="regular-text" placeholder="e.g. 218911234567" />
                    <p class="description"><?php esc_html_e('The phone number to receive admin notifications.', 'ersaal'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ersaal_wc_admin_new_order_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label></th>
                <td>
                    <textarea id="ersaal_wc_admin_new_order_template" name="ersaal_wc_admin_new_order_template" rows="3" class="large-text ersaal-template-input" data-preview="preview_admin_new_order"><?php echo esc_textarea($admin_template); ?></textarea>
                    
                    <div style="margin-top: 10px; background: #e5f5fa; padding: 10px; border-left: 4px solid #00a0d2;">
                        <strong><?php esc_html_e('Preview:', 'ersaal'); ?></strong><br/>
                        <span id="preview_admin_new_order" style="white-space: pre-wrap;"></span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <?php if (function_exists('wc_get_order_statuses')): ?>
        <hr>
        <h2><?php esc_html_e('Additional Order Statuses', 'ersaal'); ?></h2>
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
        <div class="ersaal-card" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background: #fff;">
            <h3><?php echo esc_html($label); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable', 'ersaal'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" <?php checked(1, $enabled); ?> />
                            <?php printf(esc_html__('Send SMS on %s', 'ersaal'), esc_html($label)); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ersaal_wc_event_<?php echo esc_attr($event); ?>_template"><?php esc_html_e('Message Template', 'ersaal'); ?></label></th>
                    <td>
                        <textarea id="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="3" class="large-text ersaal-template-input" data-preview="preview_<?php echo esc_attr($event); ?>"><?php echo esc_textarea($template); ?></textarea>
                        
                        <div style="margin-top: 10px; background: #f9f9f9; padding: 10px; border-left: 4px solid #00a0d2;">
                            <strong><?php esc_html_e('Preview:', 'ersaal'); ?></strong><br/>
                            <span id="preview_<?php echo esc_attr($event); ?>" style="white-space: pre-wrap;"></span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php submit_button(); ?>
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


