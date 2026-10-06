<?php
declare(strict_types=1);

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!defined('ABSPATH')) {
    exit;
}

// Clean up database if the user has opted in
$ersaal_clean_on_uninstall = (bool) get_option('ersaal_clean_on_uninstall', false);

if ($ersaal_clean_on_uninstall) {
    global $wpdb;

    // Drop plugin-owned activity tables.
    $ersaal_sms_table = esc_sql($wpdb->prefix . 'ersaal_logs');
    $ersaal_otp_table = esc_sql($wpdb->prefix . 'ersaal_otp_logs');

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_sms_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS `{$ersaal_otp_table}`"); // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    
    // Delete options
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ersaal_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ersaal_%' OR option_name LIKE '_transient_timeout_ersaal_%'");

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
