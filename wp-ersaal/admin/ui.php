<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
/**
 * Return allowed HTML tags for Ersaal admin SVG icons.
 *
 * @return array<string, array<string, bool>>
 */
function ersaal_allowed_svg_tags(): array
{
    return [
        'svg' => [
            'class' => true,
            'viewbox' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'aria-hidden' => true,
            'focusable' => true,
        ],
        'path' => [
            'd' => true,
        ],
        'rect' => [
            'width' => true,
            'height' => true,
            'x' => true,
            'y' => true,
            'rx' => true,
        ],
        'circle' => [
            'cx' => true,
            'cy' => true,
            'r' => true,
        ],
    ];
}

/**
 * Render a small, consistent Ersaal admin icon.
 */
function ersaal_admin_icon(string $name, string $class = ''): string
{
    $paths = [
        'arrow' => '<path d="m9 18 6-6-6-6"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'close' => '<path d="m18 6-12 12M6 6l12 12"/>',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'help' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2-3 4M12 18h.01"/>',
        'logs' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v6M14 11v6"/>',
        'warning' => '<path d="M10.3 2.9 1.8 17a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 2.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01"/>',
    ];

    if (!isset($paths[$name])) {
        return '';
    }

    $classes = trim('ersaal-icon ersaal-icon-' . $name . ' ' . $class);

    return wp_kses(
        sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
            esc_attr($classes),
            $paths[$name]
        ),
        ersaal_allowed_svg_tags()
    );
}

/**
 * Return a localized label for an internal message status.
 */
function ersaal_admin_status_label(string $status): string
{
    $labels = [
        'processing' => __('Processing', 'ersaal'),
        'accepted' => __('Accepted', 'ersaal'),
        'delivered' => __('Delivered', 'ersaal'),
        'sent' => __('Sent', 'ersaal'),
        'retry_scheduled' => __('Retry scheduled', 'ersaal'),
        'queued' => __('Queued', 'ersaal'),
        'pending' => __('Pending', 'ersaal'),
        'failed' => __('Failed', 'ersaal'),
        'error' => __('Error', 'ersaal'),
        'rejected' => __('Rejected', 'ersaal'),
        'expired' => __('Expired', 'ersaal'),
        'unknown' => __('Unknown', 'ersaal'),
    ];

    return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
}

/**
 * Return a localized label for an internal message source.
 */
function ersaal_admin_source_label(string $source): string
{
    $labels = ersaal_admin_log_sources();

    $label = $labels[$source] ?? ucwords(str_replace('_', ' ', $source));

    return (string) apply_filters('ersaal_log_source_label', $label, $source);
}

/**
 * Return a localized label for a core or custom WooCommerce event.
 */
function ersaal_admin_event_label(string $event): string
{
    $labels = [
        'new_order' => __('New order', 'ersaal'),
        'processing' => __('Processing', 'ersaal'),
        'completed' => __('Completed', 'ersaal'),
        'cancelled' => __('Cancelled', 'ersaal'),
    ];

    if (isset($labels[$event])) {
        return (string) apply_filters('ersaal_log_event_label', $labels[$event], $event);
    }

    if (function_exists('wc_get_order_status_name')) {
        $woocommerceLabel = wc_get_order_status_name($event);
        if (is_string($woocommerceLabel) && $woocommerceLabel !== '' && $woocommerceLabel !== $event) {
            return (string) apply_filters('ersaal_log_event_label', $woocommerceLabel, $event);
        }
    }

    $label = ucwords(str_replace('_', ' ', $event));
    return (string) apply_filters('ersaal_log_event_label', $label, $event);
}

/**
 * Return the extensible source list used by the log filter.
 *
 * @return array<string, string>
 */
function ersaal_admin_log_sources(): array
{
    $sources = apply_filters('ersaal_log_sources', [
        'manual' => __('Manual', 'ersaal'),
        'woocommerce' => __('WooCommerce', 'ersaal'),
    ]);

    return is_array($sources) ? $sources : [];
}

/**
 * Return a source reference label without coupling the log UI to integrations.
 */
function ersaal_admin_log_reference_label(object $log): string
{
    $label = '';
    if (($log->source ?? '') === 'woocommerce' && !empty($log->source_id)) {
        /* translators: %s: Order ID */
        $label = sprintf(__('Order #%s', 'ersaal'), (string) $log->source_id);
    }

    return (string) apply_filters('ersaal_log_reference_label', $label, $log);
}

/**
 * Return a source reference URL without coupling the log UI to integrations.
 */
function ersaal_admin_log_reference_url(object $log): string
{
    $url = '';
    if (($log->source ?? '') === 'woocommerce' && !empty($log->source_id) && function_exists('get_edit_post_link')) {
        $url = (string) get_edit_post_link((int) $log->source_id);
    }

    return (string) apply_filters('ersaal_log_reference_url', $url, $log);
}
