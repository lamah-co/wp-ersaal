<?php
declare(strict_types=1);

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!defined('ABSPATH')) {
    exit;
}

// Clear any scheduled WP-Cron hooks.
wp_clear_scheduled_hook('ersaal_daily_log_cleanup');

// Clean up database only if the user has explicitly opted in.
// Note: Strings like "false", "0", or empty strings must NOT be treated as truthy.
$raw_clean = get_option('ersaal_clean_on_uninstall', false);
$ersaal_clean_on_uninstall = ($raw_clean === true || $raw_clean === 1 || $raw_clean === '1');

if ($ersaal_clean_on_uninstall) {
    global $wpdb;

    // Drop plugin-owned activity tables.
    $ersaal_sms_table = esc_sql($wpdb->prefix . 'ersaal_logs');
    $ersaal_otp_table = esc_sql($wpdb->prefix . 'ersaal_otp_logs');

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_sms_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_otp_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter

    // Delete options using escaped literal prefixes and prepared queries.
    // _ in LIKE is a single-character wildcard; $wpdb->esc_like() ensures only exact prefixes match.
    $like_options = $wpdb->esc_like('ersaal_') . '%';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like_options
        )
    );

    $like_transients = $wpdb->esc_like('_transient_ersaal_') . '%';
    $like_transient_timeouts = $wpdb->esc_like('_transient_timeout_ersaal_') . '%';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like_transients,
            $like_transient_timeouts
        )
    );

    if (is_multisite() && !empty($wpdb->sitemeta)) {
        $like_site_transients = $wpdb->esc_like('_site_transient_ersaal_') . '%';
        $like_site_transient_timeouts = $wpdb->esc_like('_site_transient_timeout_ersaal_') . '%';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                $like_site_transients,
                $like_site_transient_timeouts
            )
        );
    }

    // Remove OTP enrollment state only when full cleanup was explicitly enabled.
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (
            'ersaal_otp_phone',
            'ersaal_otp_phone_verified',
            'ersaal_otp_phone_verified_at',
            'ersaal_otp_login_2fa'
        )"
    );
    // phpcs:enable
}
