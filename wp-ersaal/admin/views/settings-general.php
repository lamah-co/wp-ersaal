<?php
if (!defined('ABSPATH')) {
    exit;
}
$is_key_defined = defined('ERSAAL_API_KEY');
$api_url = $this->options->get('api_url', 'https://api.ersaal.com/');
$has_api_key = !empty($this->options->get('api_key', ''));
?>
<form method="post" action="options.php">
    <?php settings_fields('ersaal_general_settings'); ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="ersaal_api_url"><?php esc_html_e('API Base URL', 'ersaal'); ?></label></th>
            <td>
                <input name="ersaal_api_url" type="url" id="ersaal_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ersaal_api_key"><?php esc_html_e('API Key', 'ersaal'); ?></label></th>
            <td>
                <?php if ($is_key_defined): ?>
                    <input type="password" value="********" class="regular-text" disabled />
                    <p class="description"><?php esc_html_e('API Key is defined in wp-config.php and cannot be changed here.', 'ersaal'); ?></p>
                <?php else: ?>
                    <input name="ersaal_api_key" type="password" id="ersaal_api_key" value="<?php echo $has_api_key ? '********' : ''; ?>" class="regular-text" placeholder="<?php esc_attr_e('Enter your Bearer Token', 'ersaal'); ?>" autocomplete="off" />
                    <?php if ($has_api_key): ?>
                        <p class="description"><?php esc_html_e('API Key is set. Leave blank or keep ******** to retain the existing key.', 'ersaal'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <?php submit_button(); ?>
</form>

<hr>
<h3><?php esc_html_e('Connection Test', 'ersaal'); ?></h3>
<p><?php esc_html_e('Test your connection to the Ersaal Gateway using the configured settings.', 'ersaal'); ?></p>
<button type="button" class="button" id="ersaal-test-api-btn"><?php esc_html_e('Test API Connection', 'ersaal'); ?></button>
<span id="ersaal-test-api-result" style="margin-left:10px; font-weight:bold;"></span>

<script>
jQuery(document).ready(function($) {
    $('#ersaal-test-api-btn').on('click', function() {
        var btn = $(this);
        var result = $('#ersaal-test-api-result');
        btn.prop('disabled', true);
        result.text('<?php esc_html_e("Testing...", "ersaal"); ?>').css('color', '#333');
        
        $.post(ajaxurl, {
            action: 'ersaal_test_api',
            nonce: '<?php echo esc_js(wp_create_nonce("ersaal_test_api")); ?>'
        }, function(response) {
            btn.prop('disabled', false);
            if (response.success) {
                result.text(response.data.message).css('color', 'green');
            } else {
                result.text(response.data.message).css('color', 'red');
            }
        });
    });
});
</script>
