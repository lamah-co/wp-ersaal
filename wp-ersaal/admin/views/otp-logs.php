<?php
if (!defined('ABSPATH')) {
    exit;
}
$pageUrl = admin_url('admin.php?page=ersaal-otp-logs');
$statusLabels = [
    'sent' => __('Sent', 'ersaal'),
    'verified' => __('Verified', 'ersaal'),
    'invalid' => __('Invalid', 'ersaal'),
    'expired' => __('Expired', 'ersaal'),
    'failed' => __('Failed', 'ersaal'),
    'rate_limited' => __('Rate limited', 'ersaal'),
    'unavailable' => __('Unavailable', 'ersaal'),
];
$contextLabels = [
    'admin_test'          => __('Admin test', 'ersaal'),
    'profile_verification'=> __('Profile verification', 'ersaal'),
    'wordpress_login'     => __('WordPress login', 'ersaal'),
    'custom'              => __('Developer integration', 'ersaal'),
];

/**
 * Filters the OTP context labels displayed in the OTP Activity log.
 *
 * External plugins can use this filter to register additional OTP
 * workflow contexts without modifying wp-ersaal.
 *
 * Example usage in an external plugin:
 *   add_filter( 'ersaal_otp_context_labels', function ( $labels ) {
 *       $labels['my_context'] = 'My Workflow Label';
 *       return $labels;
 *   } );
 *
 * @param array $contextLabels Associative array of context_key => label.
 */
$contextLabels = apply_filters( 'ersaal_otp_context_labels', $contextLabels );
$badgeType = static function (string $value): string {
    if (in_array($value, ['sent', 'verified'], true)) {
        return 'success';
    }
    if (in_array($value, ['invalid', 'expired', 'rate_limited'], true)) {
        return 'warning';
    }
    return 'danger';
};
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-wide ersaal-otp-logs-page">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('OTP Activity', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Review OTP requests and verification outcomes without exposing codes or full phone numbers.', 'ersaal'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <a class="ersaal-btn ersaal-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-otp-test')); ?>"><?php esc_html_e('Run OTP Test', 'ersaal'); ?></a>
            <a class="ersaal-btn ersaal-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>"><?php esc_html_e('View SMS logs', 'ersaal'); ?></a>
        </div>
    </header>

    <form method="get" class="ersaal-filter-form">
        <input type="hidden" name="page" value="ersaal-otp-logs" />
        <div class="ersaal-field">
            <label for="ersaal-otp-log-status"><?php esc_html_e('Status', 'ersaal'); ?></label>
            <select class="ersaal-select" id="ersaal-otp-log-status" name="status">
                <option value="all"><?php esc_html_e('All statuses', 'ersaal'); ?></option>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ersaal-field">
            <label for="ersaal-otp-log-context"><?php esc_html_e('Workflow', 'ersaal'); ?></label>
            <select class="ersaal-select" id="ersaal-otp-log-context" name="context">
                <option value="all"><?php esc_html_e('All workflows', 'ersaal'); ?></option>
                <?php foreach ($contextLabels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($context, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ersaal-filter-actions">
            <button type="submit" class="ersaal-btn ersaal-btn-primary"><?php esc_html_e('Apply filters', 'ersaal'); ?></button>
            <?php if ($status !== 'all' || $context !== 'all'): ?>
                <a class="ersaal-btn ersaal-btn-ghost" href="<?php echo esc_url($pageUrl); ?>"><?php esc_html_e('Clear', 'ersaal'); ?></a>
            <?php endif; ?>
        </div>
    </form>

    <section class="ersaal-card">
        <div class="ersaal-toolbar">
            <div>
                <h2><?php esc_html_e('Recent OTP activity', 'ersaal'); ?></h2>
                <p class="ersaal-table-meta"><?php printf(esc_html(_n('%s activity', '%s activities', $logsData['total'], 'ersaal')), esc_html(number_format_i18n($logsData['total']))); ?></p>
            </div>
        </div>
        <?php if (empty($logsData['items'])): ?>
            <div class="ersaal-empty-state">
                <h3 class="ersaal-empty-state-title"><?php esc_html_e('No OTP activity yet', 'ersaal'); ?></h3>
                <p class="ersaal-empty-state-text"><?php esc_html_e('Run an OTP test or verify a user phone to create the first safe activity record.', 'ersaal'); ?></p>
            </div>
        <?php else: ?>
            <div class="ersaal-table-wrap">
                <table class="wp-list-table widefat ersaal-table">
                    <thead><tr>
                        <th><?php esc_html_e('Status', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Action', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Workflow', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Phone', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Reference', 'ersaal'); ?></th>
                        <th><?php esc_html_e('Created', 'ersaal'); ?></th>
                        <th class="column-actions"><?php esc_html_e('Actions', 'ersaal'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($logsData['items'] as $log): ?>
                        <?php
                        $logDetails = (array) $log;
                        $logDetails['status'] = $statusLabels[$log->status] ?? ucwords(str_replace('_', ' ', (string) $log->status));
                        $logDetails['context'] = $contextLabels[$log->context] ?? ucwords(str_replace('_', ' ', (string) $log->context));
                        $logDetails['action'] = $log->action === 'verify' ? __('Verify', 'ersaal') : __('Send', 'ersaal');
                        ?>
                        <tr>
                            <td><span class="ersaal-badge ersaal-badge-<?php echo esc_attr($badgeType((string) $log->status)); ?>"><?php echo esc_html($logDetails['status']); ?></span></td>
                            <td><?php echo esc_html($logDetails['action']); ?></td>
                            <td><?php echo esc_html($logDetails['context']); ?></td>
                            <td><span class="ersaal-code ersaal-ltr"><?php echo esc_html($log->phone_masked); ?></span></td>
                            <td><span class="ersaal-code ersaal-ltr"><?php echo esc_html($log->reference ? mb_substr((string) $log->reference, 0, 12) . '…' : '—'); ?></span></td>
                            <td><time class="ersaal-table-meta ersaal-ltr" datetime="<?php echo esc_attr($log->created_at); ?>"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime((string) $log->created_at . ' UTC'))); ?></time></td>
                            <td>
                                <div class="ersaal-inline-actions ersaal-log-actions">
                                    <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm ersaal-view-otp-log" data-log="<?php echo esc_attr(wp_json_encode($logDetails)); ?>"><?php esc_html_e('Details', 'ersaal'); ?></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($logsData['pages'] > 1): ?>
                <nav class="ersaal-pagination" aria-label="<?php esc_attr_e('OTP activity pagination', 'ersaal'); ?>">
                    <?php echo wp_kses_post(paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('Previous', 'ersaal'),
                        'next_text' => __('Next', 'ersaal'),
                        'total' => $logsData['pages'],
                        'current' => $logsData['page'],
                    ])); ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <div id="ersaal-otp-log-modal" class="ersaal-modal" aria-hidden="true">
        <section class="ersaal-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ersaal-otp-log-modal-title" tabindex="-1">
            <header class="ersaal-modal-header">
                <h2 id="ersaal-otp-log-modal-title" class="ersaal-modal-title"><?php esc_html_e('Log details', 'ersaal'); ?></h2>
                <button type="button" id="ersaal-otp-close-modal" class="ersaal-btn ersaal-btn-ghost ersaal-icon-button" aria-label="<?php esc_attr_e('Close details', 'ersaal'); ?>"><?php echo ersaal_admin_icon('close'); ?></button>
            </header>
            <div class="ersaal-modal-body">
                <dl id="ersaal-otp-log-detail-list" class="ersaal-detail-list"></dl>
            </div>
        </section>
    </div>
</div>
