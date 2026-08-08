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
    <p class="description">
        <?php esc_html_e('Available variables:', 'ersaal'); ?> 
        <code>{customer_name}</code>, <code>{order_number}</code>, <code>{order_total}</code>, <code>{order_status}</code>, <code>{site_name}</code>, <code>{billing_first_name}</code>, <code>{billing_last_name}</code>
    </p>

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
    </div>
    <?php endforeach; ?>

    <?php submit_button(); ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dummyData = {
        '{customer_name}': 'أحمد محمد',
        '{order_number}': '1025',
        '{order_total}': '150.00',
        '{order_status}': 'مكتمل',
        '{site_name}': 'متجري',
        '{billing_first_name}': 'أحمد',
        '{billing_last_name}': 'محمد'
    };

    const inputs = document.querySelectorAll('.ersaal-template-input');
    
    inputs.forEach(function(input) {
        const previewId = input.getAttribute('data-preview');
        const previewEl = document.getElementById(previewId);
        
        function updatePreview() {
            let val = input.value;
            for (const [key, value] of Object.entries(dummyData)) {
                val = val.replace(new RegExp(key, 'g'), value);
            }
            previewEl.textContent = val;
        }

        input.addEventListener('input', updatePreview);
        updatePreview();
    });
});
</script>
