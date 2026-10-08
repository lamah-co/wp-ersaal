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

// Clean up database if the user has opted in
$ersaal_clean_on_uninstall = filter_var(
    get_option('ersaal_clean_on_uninstall', false),
    FILTER_VALIDATE_BOOLEAN
);

if ($ersaal_clean_on_uninstall) {
    global $wpdb;

    // Drop plugin-owned activity tables.
    $ersaal_sms_table = esc_sql($wpdb->prefix . 'ersaal_logs');
    $ersaal_otp_table = esc_sql($wpdb->prefix . 'ersaal_otp_logs');

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_sms_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_otp_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    
    // Escape underscores in LIKE prefixes so cleanup only matches Ersaal keys.
    $ersaal_option_pattern = $wpdb->esc_like('ersaal_') . '%';
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $ersaal_option_pattern
    ));

    $ersaal_transient_pattern = $wpdb->esc_like('_transient_ersaal_') . '%';
    $ersaal_transient_timeout_pattern = $wpdb->esc_like('_transient_timeout_ersaal_') . '%';
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $ersaal_transient_pattern,
        $ersaal_transient_timeout_pattern
    ));

    // Remove OTP enrollment state only when full cleanup was explicitly enabled.
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN (%s, %s, %s, %s)",
        'ersaal_otp_phone',
        'ersaal_otp_phone_verified',
        'ersaal_otp_phone_verified_at',
        'ersaal_otp_login_2fa'
    ));
    // phpcs:enable
}
