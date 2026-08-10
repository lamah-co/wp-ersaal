<?php
declare(strict_types=1);

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up database if the user has opted in
$clean_on_uninstall = get_option('ersaal_clean_on_uninstall', false);

if ($clean_on_uninstall) {
    global $wpdb;
    
    // Drop plugin-owned activity tables.
    $sms_table_name = $wpdb->prefix . 'ersaal_logs';
    $otp_table_name = $wpdb->prefix . 'ersaal_otp_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$sms_table_name}");
    $wpdb->query("DROP TABLE IF EXISTS {$otp_table_name}");
    
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
}
