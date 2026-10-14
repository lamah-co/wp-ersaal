<?php
declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}

$enabled = get_option('ersaal_wc_enable', false);
$sender = get_option('ersaal_wc_sender_id', 'Lamah');
$payment = get_option('ersaal_wc_payment_type', 'wallet');
$variables = ['{customer_name}', '{order_number}', '{order_total}', '{order_status}', '{site_name}', '{billing_first_name}', '{billing_last_name}'];
$coreEvents = [
    'new_order' => __('New order', 'ersaal-sms-gateway'),
    'processing' => __('Processing', 'ersaal-sms-gateway'),
    'completed' => __('Completed', 'ersaal-sms-gateway'),
    'cancelled' => __('Cancelled', 'ersaal-sms-gateway'),
];

$renderEventEditor = static function (string $event, string $label, bool $open = false): void {
    $eventEnabled = (bool) get_option("ersaal_wc_event_{$event}_enable", false);
    $template = (string) get_option("ersaal_wc_event_{$event}_template", '');
    $fieldId = 'ersaal_wc_event_' . $event . '_template';
    $previewId = 'preview_' . $event;
    ?>
    <details class="ersaal-event-editor"<?php if ($open): ?> open<?php endif; ?>>
        <summary>
            <span class="ersaal-event-name"><?php echo esc_html($label); ?></span>
            <span id="state_<?php echo esc_attr($event); ?>" class="ersaal-badge <?php echo esc_attr($eventEnabled ? 'ersaal-badge-success' : 'ersaal-badge-muted'); ?>">
                <?php echo esc_html($eventEnabled ? __('Enabled', 'ersaal-sms-gateway') : __('Disabled', 'ersaal-sms-gateway')); ?>
            </span>
            <?php echo ersaal_admin_icon('chevron', 'ersaal-event-chevron'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </summary>
        <div class="ersaal-event-content">
            <div class="ersaal-form-stack">
                <label class="ersaal-switch-row" for="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable">
                    <span class="ersaal-switch-copy">
                        <?php /* translators: %s: Order event label (e.g. New order, Completed) */ ?>
                        <span class="ersaal-switch-title"><?php printf(esc_html__('Send SMS for %s', 'ersaal-sms-gateway'), esc_html($label)); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Uses the template below when this event occurs.', 'ersaal-sms-gateway'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input class="ersaal-event-toggle" type="checkbox" id="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_enable" value="1" data-state="state_<?php echo esc_attr($event); ?>" <?php checked(1, $eventEnabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="<?php echo esc_attr($fieldId); ?>"><?php esc_html_e('Message template', 'ersaal-sms-gateway'); ?></label>
                    <textarea id="<?php echo esc_attr($fieldId); ?>" name="ersaal_wc_event_<?php echo esc_attr($event); ?>_template" rows="5" class="ersaal-textarea ersaal-template-input" data-preview="<?php echo esc_attr($previewId); ?>"><?php echo esc_textarea($template); ?></textarea>
                </div>
            </div>
            <div class="ersaal-template-preview" aria-live="polite">
                <div class="ersaal-template-preview-label"><?php esc_html_e('Preview', 'ersaal-sms-gateway'); ?></div>
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
                <h2 id="ersaal-wc-general-title"><?php esc_html_e('WooCommerce messaging', 'ersaal-sms-gateway'); ?></h2>
                <p><?php esc_html_e('Set the global sender and payment defaults used by order notifications.', 'ersaal-sms-gateway'); ?></p>
            </header>
            <div class="ersaal-wc-general-grid">
                <label class="ersaal-switch-row" for="ersaal_wc_enable">
                    <span class="ersaal-switch-copy">
                        <span class="ersaal-switch-title"><?php esc_html_e('Enable WooCommerce SMS', 'ersaal-sms-gateway'); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Allow enabled order events to send notifications.', 'ersaal-sms-gateway'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input type="checkbox" id="ersaal_wc_enable" name="ersaal_wc_enable" value="1" <?php checked(1, $enabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_sender_id"><?php esc_html_e('Sender ID', 'ersaal-sms-gateway'); ?></label>
                    <input type="text" id="ersaal_wc_sender_id" name="ersaal_wc_sender_id" value="<?php echo esc_attr($sender); ?>" class="ersaal-input ersaal-ltr" />
                </div>
                <div class="ersaal-field">
                    <label class="ersaal-label" for="ersaal_wc_payment_type"><?php esc_html_e('Payment type', 'ersaal-sms-gateway'); ?></label>
                    <select id="ersaal_wc_payment_type" name="ersaal_wc_payment_type" class="ersaal-select">
                        <option value="wallet" <?php selected('wallet', $payment); ?>><?php esc_html_e('Wallet', 'ersaal-sms-gateway'); ?></option>
                        <option value="subscription" <?php selected('subscription', $payment); ?>><?php esc_html_e('Subscription', 'ersaal-sms-gateway'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-customer-notifications-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-customer-notifications-title" class="ersaal-section-title"><?php esc_html_e('Customer notifications', 'ersaal-sms-gateway'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('Expand only the order event you want to configure.', 'ersaal-sms-gateway'); ?></p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm">
                <?php echo ersaal_admin_icon('help'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php esc_html_e('Template help', 'ersaal-sms-gateway'); ?>
            </a>
        </div>

        <div class="ersaal-variable-library">
            <span class="ersaal-variable-library-title"><?php esc_html_e('Insert a variable into the focused template', 'ersaal-sms-gateway'); ?></span>
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
                <h2 id="ersaal-admin-notifications-title" class="ersaal-section-title"><?php esc_html_e('Admin notifications', 'ersaal-sms-gateway'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('Notify a store administrator when a new order is placed.', 'ersaal-sms-gateway'); ?></p>
            </div>
        </div>
        <?php
        $adminEnabled = (bool) get_option('ersaal_wc_admin_new_order_enable', false);
        $adminPhone = (string) get_option('ersaal_wc_admin_phone', '');
        $adminTemplate = (string) get_option(
            'ersaal_wc_admin_new_order_template',
            __('New order #{order_number} from {customer_name}. Total: {order_total}', 'ersaal-sms-gateway')
        );
        ?>
        <div class="ersaal-settings-panel">
            <div class="ersaal-settings-section">
                <label class="ersaal-switch-row" for="ersaal_wc_admin_new_order_enable">
                    <span class="ersaal-switch-copy">
                        <span class="ersaal-switch-title"><?php esc_html_e('New order SMS for admin', 'ersaal-sms-gateway'); ?></span>
                        <span class="ersaal-switch-description"><?php esc_html_e('Send one message to the number below for each new order.', 'ersaal-sms-gateway'); ?></span>
                    </span>
                    <span class="ersaal-switch-control">
                        <input type="checkbox" id="ersaal_wc_admin_new_order_enable" name="ersaal_wc_admin_new_order_enable" value="1" <?php checked(1, $adminEnabled); ?> />
                        <span class="ersaal-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <div class="ersaal-event-content">
                    <div class="ersaal-form-stack">
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_wc_admin_phone"><?php esc_html_e('Admin phone number', 'ersaal-sms-gateway'); ?></label>
                            <input type="tel" id="ersaal_wc_admin_phone" name="ersaal_wc_admin_phone" value="<?php echo esc_attr($adminPhone); ?>" class="ersaal-input ersaal-ltr" placeholder="0912345678" autocomplete="tel" />
                            <span class="ersaal-help-text"><?php esc_html_e('Libyana or Almadar: 091 / 092 / 093 / 094', 'ersaal-sms-gateway'); ?></span>
                        </div>
                        <div class="ersaal-field">
                            <label class="ersaal-label" for="ersaal_wc_admin_new_order_template"><?php esc_html_e('Message template', 'ersaal-sms-gateway'); ?></label>
                            <textarea id="ersaal_wc_admin_new_order_template" name="ersaal_wc_admin_new_order_template" rows="5" class="ersaal-textarea ersaal-template-input" data-preview="preview_admin_new_order"><?php echo esc_textarea($adminTemplate); ?></textarea>
                        </div>
                    </div>
                    <div class="ersaal-template-preview" aria-live="polite">
                        <div class="ersaal-template-preview-label"><?php esc_html_e('Preview', 'ersaal-sms-gateway'); ?></div>
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
                        <h2 id="ersaal-status-notifications-title" class="ersaal-section-title"><?php esc_html_e('Additional order statuses', 'ersaal-sms-gateway'); ?></h2>
                        <p class="ersaal-section-description"><?php esc_html_e('Optional notifications for statuses registered by WooCommerce or other extensions.', 'ersaal-sms-gateway'); ?></p>
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
                    <h2 id="ersaal-manual-order-title"><?php esc_html_e('Manual order messaging', 'ersaal-sms-gateway'); ?></h2>
                    <p><?php esc_html_e('Manual SMS is available from the Ersaal panel on each WooCommerce order.', 'ersaal-sms-gateway'); ?></p>
                </header>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#woocommerce')); ?>" class="ersaal-btn ersaal-btn-ghost ersaal-btn-sm"><?php esc_html_e('Read how it works', 'ersaal-sms-gateway'); ?><?php echo ersaal_admin_icon('arrow'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
            </div>
        </div>
    </section>

    <div class="ersaal-save-bar">
        <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Save WooCommerce settings', 'ersaal-sms-gateway'); ?></button>
    </div>
</form>

