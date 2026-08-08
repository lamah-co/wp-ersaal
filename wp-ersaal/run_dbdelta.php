<?php
require_once dirname(__DIR__, 3) . '/wp-load.php';
ersaal_plugin()->getContainer()->get(\Ersaal\Core\Database::class)->installTables();
echo "Tables installed successfully.\n";
