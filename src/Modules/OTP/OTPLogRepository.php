<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

final class OTPLogRepository
{
    public function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'ersaal_otp_logs';
    }

    public function create(array $data): int
    {
        global $wpdb;
        $table = $this->getTableName();
        $now = current_time('mysql', true);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->insert($table, [
            'action' => sanitize_key((string) ($data['action'] ?? 'initiate')),
            'status' => sanitize_key((string) ($data['status'] ?? 'failed')),
            'phone_hash' => (string) ($data['phone_hash'] ?? ''),
            'phone_masked' => sanitize_text_field((string) ($data['phone_masked'] ?? '')),
            'reference' => !empty($data['reference']) ? sanitize_text_field((string) $data['reference']) : null,
            'context' => sanitize_key((string) ($data['context'] ?? 'custom')),
            'user_id' => !empty($data['user_id']) ? absint($data['user_id']) : null,
            'api_http_code' => !empty($data['api_http_code']) ? absint($data['api_http_code']) : null,
            'error_code' => !empty($data['error_code']) ? sanitize_key((string) $data['error_code']) : null,
            'error_message' => !empty($data['error_message']) ? mb_substr(sanitize_text_field((string) $data['error_message']), 0, 191) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === false) {
            throw new \RuntimeException(esc_html__('Unable to create OTP activity log.', 'ersaal'));
        }
        return (int) $wpdb->insert_id;
    }

    public function getStatsToday(): array
    {
        global $wpdb;
        $table = $this->getTableName();
        // Activity timestamps are stored in UTC, so the daily boundary must use
        // the same clock to avoid skew around the site's local midnight.
        $today = gmdate('Y-m-d');
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS total FROM {$table} WHERE DATE(created_at) = %s GROUP BY status",
            $today
        ), ARRAY_A);
        // phpcs:enable
        $stats = ['requests' => 0, 'verified' => 0, 'failed' => 0, 'verification_rate' => 0.0];
        foreach ($rows ?: [] as $row) {
            $count = (int) $row['total'];
            if ($row['status'] === 'sent') {
                $stats['requests'] += $count;
            } elseif ($row['status'] === 'verified') {
                $stats['verified'] += $count;
            } elseif (in_array($row['status'], ['failed', 'invalid', 'expired', 'rate_limited', 'unavailable'], true)) {
                $stats['failed'] += $count;
            }
        }
        $attempts = $stats['verified'] + $stats['failed'];
        if ($attempts > 0) {
            $stats['verification_rate'] = round(($stats['verified'] / $attempts) * 100, 1);
        }
        return $stats;
    }

    public function getRecent(int $limit = 8): array
    {
        global $wpdb;
        $table = $this->getTableName();
        $limit = max(1, min(50, $limit));
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $recentRows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, action, status, phone_masked, reference, context, user_id, api_http_code, error_code, error_message, created_at, updated_at FROM {$table} ORDER BY id DESC LIMIT %d",
            $limit
        )) ?: [];
        // phpcs:enable
        return $recentRows;
    }

    public function getLogs(array $args = []): array
    {
        global $wpdb;
        $table = $this->getTableName();
        $page = max(1, (int) ($args['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($args['per_page'] ?? 20)));
        $status = sanitize_key((string) ($args['status'] ?? 'all'));
        $context = sanitize_key((string) ($args['context'] ?? 'all'));
        $where = ['1=1'];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $where[] = 'status = %s';
            $params[] = $status;
        }
        if ($context !== '' && $context !== 'all') {
            $where[] = 'context = %s';
            $params[] = $context;
        }
        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        $queryParams = array_merge($params, [$perPage, $offset]);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE {$whereSql} ORDER BY id DESC LIMIT %d OFFSET %d", ...$queryParams);
        $countQuery = empty($params)
            ? "SELECT COUNT(id) FROM {$table} WHERE {$whereSql}"
            : $wpdb->prepare("SELECT COUNT(id) FROM {$table} WHERE {$whereSql}", ...$params);
        $total = (int) $wpdb->get_var($countQuery);
        $items = $wpdb->get_results($query) ?: [];
        // phpcs:enable

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
            'page' => $page,
            'per_page' => $perPage,
        ];
    }
}
