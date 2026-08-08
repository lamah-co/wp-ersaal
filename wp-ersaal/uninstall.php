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
    
    // Drop logs table
    $table_name = $wpdb->prefix . 'ersaal_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Delete options
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ersaal_%'");
}
