<?php
if (!defined('ABSPATH')) {
    exit;
}

$statusBadges = [
    'accepted' => 'ersaal-badge-success',
    'delivered' => 'ersaal-badge-success',
    'sent' => 'ersaal-badge-success',
    'processing' => 'ersaal-badge-info',
    'pending' => 'ersaal-badge-info',
    'error' => 'ersaal-badge-danger',
    'failed' => 'ersaal-badge-danger',
    'rejected' => 'ersaal-badge-danger',
    'retry_scheduled' => 'ersaal-badge-warning',
    'queued' => 'ersaal-badge-warning',
];

$subscriptionNames = [];
foreach ($connection['subscriptions'] as $subscription) {
    if (!empty($subscription['plan_name'])) {
        $subscriptionNames[] = (string) $subscription['plan_name'];
    }
}
$subscriptionLabel = $subscriptionNames ? implode(', ', $subscriptionNames) : __('Not available', 'ersaal');
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-wide">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php esc_html_e('Dashboard', 'ersaal'); ?></h1>
            <p class="ersaal-page-description"><?php esc_html_e('Monitor connection readiness and today’s messaging activity.', 'ersaal'); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-send-message')); ?>" class="ersaal-btn ersaal-btn-primary">
                <?php echo ersaal_admin_icon('send'); ?>
                <?php esc_html_e('Send SMS', 'ersaal'); ?>
            </a>
        </div>
    </header>

    <section class="ersaal-section" aria-labelledby="ersaal-connection-title">
        <div class="ersaal-card ersaal-connection-card">
            <div class="ersaal-connection-heading">
                <h2 id="ersaal-connection-title" class="ersaal-card-title"><?php esc_html_e('Ersaal Connection', 'ersaal'); ?></h2>
                <span class="ersaal-status-line <?php echo $connection['connected'] ? 'is-success' : 'is-danger'; ?>">
                    <span class="ersaal-status-dot" aria-hidden="true"></span>
                    <?php echo esc_html($connection['connected'] ? __('Connected', 'ersaal') : __('Not connected', 'ersaal')); ?>
                </span>
            </div>

            <dl class="ersaal-summary-list">
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('Project', 'ersaal'); ?></dt>
                    <dd><?php echo esc_html($connection['project_name'] ?: __('Not available', 'ersaal')); ?></dd>
                </div>
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('Project status', 'ersaal'); ?></dt>
                    <dd><?php echo esc_html(ucfirst((string) $connection['status'])); ?></dd>
                </div>
                <div class="ersaal-summary-item">
                    <dt><?php esc_html_e('Subscription', 'ersaal'); ?></dt>
                    <dd><?php echo esc_html($subscriptionLabel); ?></dd>
                </div>
            </dl>

            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-prominent ersaal-connection-settings-action">
                <?php echo ersaal_admin_icon('settings'); ?>
                <?php esc_html_e('Connection settings', 'ersaal'); ?>
            </a>
        </div>
        <?php if (!$connection['connected'] && !empty($connection['error'])): ?>
            <div class="ersaal-alert ersaal-alert-warning" role="status">
                <p class="ersaal-alert-title"><?php echo ersaal_admin_icon('warning'); ?><?php esc_html_e('Connection needs attention', 'ersaal'); ?></p>
                <p><?php echo esc_html($connection['error']); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-today-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-today-title" class="ersaal-section-title"><?php esc_html_e('Today’s activity', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('A current snapshot of local message records.', 'ersaal'); ?></p>
            </div>
        </div>
        <div class="ersaal-stat-grid">
            <article class="ersaal-card-stat">
                <span class="ersaal-stat-label"><?php esc_html_e('Messages today', 'ersaal'); ?></span>
                <strong class="ersaal-stat-value"><?php echo esc_html(number_format_i18n($stats['total_today'])); ?></strong>
                <span class="ersaal-stat-context"><?php esc_html_e('All statuses', 'ersaal'); ?></span>
            </article>
            <article class="ersaal-card-stat ersaal-stat-success">
                <span class="ersaal-stat-label"><?php esc_html_e('Accepted', 'ersaal'); ?></span>
                <strong class="ersaal-stat-value"><?php echo esc_html(number_format_i18n($stats['accepted_today'])); ?></strong>
                <span class="ersaal-stat-context"><?php esc_html_e('Accepted by Ersaal', 'ersaal'); ?></span>
            </article>
            <article class="ersaal-card-stat ersaal-stat-danger">
                <span class="ersaal-stat-label"><?php esc_html_e('Failed', 'ersaal'); ?></span>
                <strong class="ersaal-stat-value"><?php echo esc_html(number_format_i18n($stats['failed_today'])); ?></strong>
                <span class="ersaal-stat-context"><?php esc_html_e('Failed or errored', 'ersaal'); ?></span>
            </article>
            <article class="ersaal-card-stat ersaal-stat-warning">
                <span class="ersaal-stat-label"><?php esc_html_e('Retrying', 'ersaal'); ?></span>
                <strong class="ersaal-stat-value"><?php echo esc_html(number_format_i18n($stats['retry_today'])); ?></strong>
                <span class="ersaal-stat-context"><?php esc_html_e('Processing or scheduled', 'ersaal'); ?></span>
            </article>
        </div>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-recent-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-recent-title" class="ersaal-section-title"><?php esc_html_e('Recent activity', 'ersaal'); ?></h2>
                <p class="ersaal-section-description"><?php esc_html_e('The latest messages across manual and WooCommerce sources.', 'ersaal'); ?></p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm ersaal-view-all-logs">
                <?php echo ersaal_admin_icon('logs'); ?>
                <?php esc_html_e('View all logs', 'ersaal'); ?>
                <?php echo ersaal_admin_icon('arrow'); ?>
            </a>
        </div>

        <?php if (empty($recent['items'])): ?>
            <div class="ersaal-card ersaal-card-flat">
                <div class="ersaal-empty-state">
                    <?php echo ersaal_admin_icon('logs'); ?>
                    <div>
                        <p class="ersaal-empty-state-title"><?php esc_html_e('No activity yet', 'ersaal'); ?></p>
                        <p class="ersaal-empty-state-text"><?php esc_html_e('Messages sent through Ersaal will appear here.', 'ersaal'); ?></p>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-send-message')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm"><?php esc_html_e('Send your first SMS', 'ersaal'); ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="ersaal-table-wrap">
                <table class="ersaal-table ersaal-dashboard-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Status', 'ersaal'); ?></th>
                            <th><?php esc_html_e('Phone', 'ersaal'); ?></th>
                            <th><?php esc_html_e('Source', 'ersaal'); ?></th>
                            <th><?php esc_html_e('Message', 'ersaal'); ?></th>
                            <th><?php esc_html_e('Created', 'ersaal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent['items'] as $log): ?>
                            <?php $badgeClass = $statusBadges[$log->status] ?? 'ersaal-badge-muted'; ?>
                            <tr>
                                <td><span class="ersaal-badge <?php echo esc_attr($badgeClass); ?>"><?php echo esc_html(ersaal_admin_status_label((string) $log->status)); ?></span></td>
                                <td><span class="ersaal-code ersaal-ltr"><?php echo esc_html($log->phone_masked); ?></span></td>
                                <td><?php echo esc_html(!empty($log->source_event) ? ersaal_admin_event_label((string) $log->source_event) : ersaal_admin_source_label((string) $log->source)); ?></td>
                                <td class="ersaal-table-message"><?php echo !empty($log->message_excerpt) ? esc_html($log->message_excerpt) : '&mdash;'; ?></td>
                                <td><time class="ersaal-table-meta ersaal-ltr" datetime="<?php echo esc_attr($log->created_at); ?>"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></time></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="ersaal-section" aria-labelledby="ersaal-actions-title">
        <div class="ersaal-section-header">
            <div>
                <h2 id="ersaal-actions-title" class="ersaal-section-title"><?php esc_html_e('Quick actions', 'ersaal'); ?></h2>
            </div>
        </div>
        <div class="ersaal-quick-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-send-message')); ?>" class="ersaal-btn ersaal-btn-primary ersaal-btn-prominent"><?php echo ersaal_admin_icon('send'); ?><?php esc_html_e('Send SMS', 'ersaal'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-prominent"><?php echo ersaal_admin_icon('logs'); ?><?php esc_html_e('View logs', 'ersaal'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings')); ?>" class="ersaal-btn ersaal-btn-secondary ersaal-btn-prominent"><?php echo ersaal_admin_icon('settings'); ?><?php esc_html_e('Settings', 'ersaal'); ?></a>
        </div>
    </section>
</div>
