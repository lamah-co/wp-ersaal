<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Database
{
    public const VERSION_KEY = 'ersaal_db_version';
    public const VERSION = '1.0.0';

    public function upgrade(): void
    {
        $current_version = get_option(self::VERSION_KEY, '0.0.0');

        if (version_compare($current_version, self::VERSION, '<')) {
            $this->installTables();
            update_option(self::VERSION_KEY, self::VERSION);
        }
    }

    public function installTables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ersaal_logs';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            idempotency_key varchar(100) NOT NULL,
            message_id varchar(36) DEFAULT NULL,
            phone_hash varchar(64) NOT NULL,
            phone_masked varchar(20) NOT NULL,
            sender varchar(50) NOT NULL,
            payment_type varchar(20) NOT NULL,
            source varchar(30) NOT NULL,
            source_id varchar(50) NOT NULL,
            source_event varchar(50) NOT NULL,
            recipient_type varchar(30) NOT NULL,
            status varchar(30) NOT NULL,
            ersaal_status varchar(30) DEFAULT NULL,
            api_http_code smallint(5) DEFAULT NULL,
            api_error varchar(255) DEFAULT NULL,
            message_excerpt varchar(150) DEFAULT NULL,
            message_text text DEFAULT NULL,
            attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
            parts_estimated tinyint(3) unsigned DEFAULT NULL,
            cost_estimated decimal(10,3) DEFAULT NULL,
            parts_final tinyint(3) unsigned DEFAULT NULL,
            cost_final decimal(10,3) DEFAULT NULL,
            next_retry_at datetime DEFAULT NULL,
            locked_at datetime DEFAULT NULL,
            last_attempt_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY idempotency_key (idempotency_key),
            KEY message_id (message_id),
            KEY phone_hash (phone_hash),
            KEY next_retry_at (next_retry_at),
            KEY status (status),
            KEY source (source),
            KEY source_id (source_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
