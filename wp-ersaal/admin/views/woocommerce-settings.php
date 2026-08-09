<?php
if (!defined('ABSPATH')) {
    exit;
}

$enabled = get_option('ersaal_wc_enable', false);
$sender = get_option('ersaal_wc_sender_id', 'Lamah');
$payment = get_option('ersaal_wc_payment_type', 'wallet');
$variables = ['{customer_name}', '{order_number}', '{order_total}', '{order_status}', '{site_name}', '{billing_first_name}', '{billing_last_name}'];
$coreEvents = [
    'new_order' => __('New order', 'ersaal'),
    'processing' => __('Processing', 'ersaal'),
    'completed' => __('Completed', 'ersaal'),
    'cancelled' => __('Cancelled', 'ersaal'),
];

$renderEventEditor = static function (string $event, string $label, bool $open = false): void {
    $eventEnabled = (bool) get_option("ersaal_wc_event_{$event}_enable", false);
    $template = (string) get_option("ersaal_wc_event_{$event}_template", '');
    $fieldId = 'ersaal_wc_event_' . $event . '_template';
    $previewId = 'preview_' . $event;
    ?>
    <details class="ersaal-event-editor" <?php echo $open ? 'open' : ''; ?>>
        <summary>
            <span class="ersaal-event-name"><?php echo esc_html($label); ?></span>
            <span id="state_<?php echo esc_attr($event); ?>" class="ersaal-badge <?php echo $eventEnabled ? 'ersaal-badge-success' : 'ersaal-badge-muted'; ?>">
                <?php echo esc_html($eventEnabled ? __('Enabled', 'ersaal') : __('Disabled', 'ersaal')); ?>
            </span>
            <?php echo ersaal_admin_icon('chevron', 'ersaal-event-chevron'); ?>
        </summary>
        <div class="ersaal-event-content">
            <div class="ersaal-form-stack">
                <label class="ersaal-switch-row" for="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable">
                    <span class="ersaal-switch-copy">
                        <span class="ersaal-switch-title"><?php printf(esc_html__('Send SMS for %s', 'ersaal'), esc_html($label)); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Uses the template below when this event occurs.', 'ersaal'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input class="ersaal-event-toggle" type="checkbox" id="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" data-state="state_<?php echo esc_attr($event); ?>" <?php checked(1, $eventEnabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="<?php echo esc_attr($fieldId); ?>"><?php esc_html_e('Message template', 'ersaal'); ?></label>
                    <textarea id="<?php echo esc_attr($fieldId); ?>" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="5" class="ersaal-textarea ersaal-template-input" data-preview="<?php echo esc_attr($previewId); ?>"><?php echo esc_textarea($template); ?></textarea>
                </div>
            </div>
            <div class="ersaal-template-preview" aria-live="polite">
                <div class="ersaal-template-preview-label"><?php esc_html_e('Preview', 'ersaal'); ?></div>
                <div id="<?php echo esc_attr($previewId); ?>"></div>
            </div>
        </div>
    </details>
    <?php
};
?>

<form method="post" action="options.php" class="ersaal-woocommerce-form">
    <?php
    settings_fields('ersaal_woocommerce_settings');
    do_settings_sections('ersaal_woocommerce_settings');
    ?>

    <section class="ersaal-settings-panel" aria-labelledby="ersaal-wc-general-title">
        <div class="ersaal-settings-section">
            <header class="ersaal-settings-section-header">
                <h2 id="ersaal-wc-general-title"><?php esc_html_e('WooCommerce messaging', 'ersaal'); ?></h2>
                <p><?php esc_html_e('Set the global sender and payment defaults used by order notifications.', 'ersaal'); ?></p>
            </header>
            <div class="ersaal-wc-general-grid">
                <label class="ersaal-switch-row" for="ersaal_wc_enable">
                    <span class="ersaal-switch-copy">
                        <span class="ersaal-switch-title"><?php esc_html_e('Enable WooCommerce SMS', 'ersaal'); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Allow enabled order events to send notifications.', 'ersaal'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input type="checkbox" id="ersaal_wc_enable" name="ersaal_wc_enable" value="1" <?php checked(1, $enabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_sender_id"><?php esc_html_e('Sender ID', 'ersaal'); ?></label>
                    <input type="text" id="ersaal_wc_sender_id" name="ersaal_wc_sender_id" value="<?php echo esc_attr($sender); ?>" class="ersaal-input ersaal-ltr" />
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_payment_type"><?php esc_html_e('Payment type', 'ersaal'); ?></label>
                    <select id="ersaal_wc_payment_type" name="ersaal_wc_payment_type" class="ersaal-select">
                        <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal'); ?></option>
                        <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-customer-notifications-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-customer-notifications-title" class="ersaal-section-title"><?php esc_html_e('Customer notifications', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('Expand only the order event you want to configure.', 'ersaal'); ?></p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm">
                <?php echo ersaal_admin_icon('help'); ?>
                <?php esc_html_e('Template help', 'ersaal'); ?>
            </a>
        </div>

        <div class="ersaal-variable-library">
            <span class="ersaal-variable-library-title"><?php esc_html_e('Insert a variable into the focused template', 'ersaal'); ?></span>
            <div class="ersaal-chip-list">
                <?php foreach ($variables as $variable): ?>
                    <button type="button" class="ersaal-chip ersaal-var-btn ersaal-ltr" data-var="<?php echo esc_attr($variable); ?>"><?php echo esc_html($variable); ?></button>
                <?php endforeach; ?>
            </div>
            <span id="ersaal-variable-status" class="ersaal-field-help" aria-live="polite"></span>
        </div>

        <div class="ersaal-event-list">
            <?php
            $eventIndex = 0;
            foreach ($coreEvents as $event => $label) {
                $renderEventEditor($event, $label, $eventIndex === 0);
                $eventIndex++;
            }
            ?>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-admin-notifications-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-admin-notifications-title" class="ersaal-section-title"><?php esc_html_e('Admin notifications', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('Notify a store administrator when a new order is placed.', 'ersaal'); ?></p>
            </div>
        </div>
        <?php
        $adminEnabled = (bool) get_option('ersaal_wc_admin_new_order_enable', false);
        $adminPhone = (string) get_option('ersaal_wc_admin_phone', '');
        $adminTemplate = (string) get_option('ersaal_wc_admin_new_order_template', 'New order #{order_number} from {customer_name}. Total: {order_total}');
        ?>
        <div class="ersaal-settings-panel">
            <div class="ersaal-settings-section">
                <label class="ersaal-switch-row" for="ersaal_wc_admin_new_order_enable">
                    <span class="ersaal-switch-copy">
                        <span class="ersaal-switch-title"><?php esc_html_e('New order SMS for admin', 'ersaal'); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Send one message to the number below for each new order.', 'ersaal'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input type="checkbox" id="ersaal_wc_admin_new_order_enable" name="ersaal_wc_admin_new_order_enable" value="1" <?php checked(1, $adminEnabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-event-content">
                    <div class="ersaal-form-stack">
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_wc_admin_phone"><?php esc_html_e('Admin phone number', 'ersaal'); ?></label>
                            <input type="tel" id="ersaal_wc_admin_phone" name="ersaal_wc_admin_phone" value="<?php echo esc_attr($adminPhone); ?>" class="ersaal-input ersaal-ltr" placeholder="+21891XXXXXXX" autocomplete="tel" />
                        </div>
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_wc_admin_new_order_template"><?php esc_html_e('Message template', 'ersaal'); ?></label>
                            <textarea id="ersaal_wc_admin_new_order_template" name="ersaal_wc_admin_new_order_template" rows="5" class="ersaal-textarea ersaal-template-input" data-preview="preview_admin_new_order"><?php echo esc_textarea($adminTemplate); ?></textarea>
                        </div>
                    </div>
                    <div class="ersaal-template-preview" aria-live="polite">
                        <div class="ersaal-template-preview-label"><?php esc_html_e('Preview', 'ersaal'); ?></div>
                        <div id="preview_admin_new_order"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (function_exists('wc_get_order_statuses')): ?>
        <?php
        $additionalStatuses = [];
        foreach (wc_get_order_statuses() as $slug => $label) {
            $event = str_replace('wc-', '', $slug);
            if (!in_array($event, ['processing', 'completed', 'cancelled'], true)) {
                $additionalStatuses[$event] = $label;
            }
        }
        ?>
        <?php if ($additionalStatuses): ?>
            <section class="ersaal-section" aria-labelledby="ersaal-status-notifications-title">
                <div class="ersaal-section-header">
                    <div>
                        <h2 id="ersaal-status-notifications-title" class="ersaal-section-title"><?php esc_html_e('Additional order statuses', 'ersaal'); ?></h2>
                        <p class="ersaal-section-description"><?php esc_html_e('Optional notifications for statuses registered by WooCommerce or other extensions.', 'ersaal'); ?></p>
                    </div>
                </div>
                <div class="ersaal-event-list">
                    <?php foreach ($additionalStatuses as $event => $label): ?>
                        <?php $renderEventEditor((string) $event, (string) $label); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="ersaal-section" aria-labelledby="ersaal-manual-order-title">
        <div class="ersaal-settings-panel">
            <div class="ersaal-settings-section">
                <header class="ersaal-settings-section-header">
                    <h2 id="ersaal-manual-order-title"><?php esc_html_e('Manual order messaging', 'ersaal'); ?></h2>
                    <p><?php esc_html_e('Manual SMS is available from the Ersaal panel on each WooCommerce order.', 'ersaal'); ?></p>
                </header>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm"><?php esc_html_e('Read how it works', 'ersaal'); ?><?php echo ersaal_admin_icon('arrow'); ?></a>
            </div>
        </div>
    </section>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save WooCommerce settings', 'ersaal'); ?></button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const i18n = <?php echo wp_json_encode([
        'characters' => __('Characters', 'ersaal'),
        'parts' => __('Estimated parts', 'ersaal'),
        'encoding' => __('Encoding', 'ersaal'),
        'empty' => __('Preview appears as you type.', 'ersaal'),
        'enabled' => __('Enabled', 'ersaal'),
        'disabled' => __('Disabled', 'ersaal'),
        'select_template' => __('Focus a message template before inserting a variable.', 'ersaal'),
    ]); ?>;
    let lastFocused = null;

    function updatePreview(textarea) {
        const preview = document.getElementById(textarea.dataset.preview);
        if (!preview) {
            return;
        }

        const text = textarea.value;
        const arabic = /[\u0600-\u06FF]/.test(text);
        const singleLimit = arabic ? 70 : 160;
        const multipartLimit = arabic ? 67 : 153;
        const parts = text.length === 0 ? 0 : (text.length > singleLimit ? Math.ceil(text.length / multipartLimit) : 1);

        preview.textContent = text || i18n.empty;

        let stats = preview.parentElement.querySelector('.ersaal-template-stats');
        if (!stats) {
            stats = document.createElement('div');
            stats.className = 'ersaal-template-stats';
            preview.parentElement.appendChild(stats);
        }
        stats.textContent = `${i18n.characters}: ${text.length} · ${i18n.parts}: ${parts} · ${i18n.encoding}: ${arabic ? 'Unicode' : 'GSM-7'}`;
    }

    document.querySelectorAll('.ersaal-template-input').forEach(function(textarea) {
        textarea.addEventListener('input', function() { updatePreview(textarea); });
        textarea.addEventListener('focus', function() {
            lastFocused = textarea;
            document.getElementById('ersaal-variable-status').textContent = '';
        });
        updatePreview(textarea);
    });

    document.querySelectorAll('.ersaal-var-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            if (!lastFocused) {
                document.getElementById('ersaal-variable-status').textContent = i18n.select_template;
                return;
            }

            const variable = button.dataset.var;
            const start = lastFocused.selectionStart;
            const end = lastFocused.selectionEnd;
            lastFocused.value = lastFocused.value.substring(0, start) + variable + lastFocused.value.substring(end);
            lastFocused.selectionStart = lastFocused.selectionEnd = start + variable.length;
            lastFocused.focus();
            updatePreview(lastFocused);
        });
    });

    document.querySelectorAll('.ersaal-event-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const badge = document.getElementById(toggle.dataset.state);
            badge.textContent = toggle.checked ? i18n.enabled : i18n.disabled;
            badge.classList.toggle('ersaal-badge-success', toggle.checked);
            badge.classList.toggle('ersaal-badge-muted', !toggle.checked);
        });
    });
});
</script>
