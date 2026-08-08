<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Activator
{
    public static function activate(): void
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $db = new Database();
        // Always run dbDelta on activation to ensure schema is correct
        $db->installTables();
        $db->upgrade();
    }
}
