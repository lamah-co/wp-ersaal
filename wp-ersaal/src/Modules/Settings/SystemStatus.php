<?php
declare(strict_types=1);

namespace Ersaal\Modules\Settings;

class SystemStatus
{
    public static function getStatus(): array
    {
        return [
            'action_scheduler' => function_exists('as_enqueue_async_action'),
            'wp_cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
        ];
    }
}
