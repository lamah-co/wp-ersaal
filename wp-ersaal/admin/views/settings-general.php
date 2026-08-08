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
    
    <div style="display: flex; flex-direction: column; gap: var(--ersaal-form-gap);">
        <div class="ersaal-field">
            <label class="ersaal-label" for="ersaal_api_url"><?php esc_html_e('API Base URL', 'ersaal'); ?></label>
            <input name="ersaal_api_url" type="url" id="ersaal_api_url" value="<?php echo esc_attr($api_url); ?>" class="ersaal-input" />
            <p class="ersaal-field-help"><?php esc_html_e('The endpoint for Ersaal Gateway API.', 'ersaal'); ?></p>
        </div>
        
        <div class="ersaal-field">
            <label class="ersaal-label" for="ersaal_api_key"><?php esc_html_e('API Key', 'ersaal'); ?></label>
            <?php if ($is_key_defined): ?>
                <input type="password" value="********" class="ersaal-input" disabled />
                <p class="ersaal-field-help ersaal-text-warning"><?php esc_html_e('API Key is defined in wp-config.php and cannot be changed here.', 'ersaal'); ?></p>
            <?php else: ?>
                <input name="ersaal_api_key" type="password" id="ersaal_api_key" value="<?php echo $has_api_key ? '********' : ''; ?>" class="ersaal-input" placeholder="<?php esc_attr_e('Enter your Bearer Token', 'ersaal'); ?>" autocomplete="off" />
                <?php if ($has_api_key): ?>
                    <p class="ersaal-field-help"><?php esc_html_e('API Key is set. Leave blank or keep ******** to retain the existing key.', 'ersaal'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: var(--ersaal-space-xl);">
        <button type="submit" class="ersaal-btn ersaal-btn-primary">
            <?php esc_html_e('Save Settings', 'ersaal'); ?>
        </button>
    </div>
</form>

<hr style="border: none; border-top: 1px solid var(--ersaal-border-color); margin-block: var(--ersaal-space-xxl);">

<h3 class="ersaal-card-title"><?php esc_html_e('Connection Test', 'ersaal'); ?></h3>
<p class="ersaal-page-description" style="margin-block-end: var(--ersaal-space-lg);"><?php esc_html_e('Test your connection to the Ersaal Gateway using the configured settings.', 'ersaal'); ?></p>
<div style="display: flex; align-items: center; gap: var(--ersaal-space-md);">
    <button type="button" class="ersaal-btn ersaal-btn-secondary" id="ersaal-test-api-btn">
        <span class="dashicons dashicons-admin-network"></span>
        <?php esc_html_e('Test API Connection', 'ersaal'); ?>
    </button>
    <span id="ersaal-test-api-result" style="font-weight: var(--ersaal-fw-medium);"></span>
</div>

<script>
jQuery(document).ready(function($) {
    $('#ersaal-test-api-btn').on('click', function() {
        var btn = $(this);
        var result = $('#ersaal-test-api-result');
        btn.prop('disabled', true);
        result.text('<?php esc_html_e("Testing...", "ersaal"); ?>').css('color', 'var(--ersaal-text-muted)');
        
        $.post(ajaxurl, {
            action: 'ersaal_test_api',
            nonce: '<?php echo esc_js(wp_create_nonce("ersaal_test_api")); ?>'
        }, function(response) {
            btn.prop('disabled', false);
            if (response.success) {
                result.text(response.data.message).css('color', 'var(--ersaal-color-success)');
            } else {
                result.text(response.data.message).css('color', 'var(--ersaal-color-danger)');
            }
        });
    });
});
</script>
