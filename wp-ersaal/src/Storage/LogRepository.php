<?php
declare(strict_types=1);

namespace Ersaal\Storage;

class LogRepository
{
    public function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'ersaal_logs';
    }

    public function getLogByKey(string $idempotency_key): ?object
    {
        global $wpdb;
        $table = $this->getTableName();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE idempotency_key = %s", $idempotency_key));
        return $row;
    }

    /**
     * Create a processing log atomically.
     * Returns true if created, false if it already exists (Race Condition prevented).
     */
    public function createProcessingLog(array $data): bool
    {
        global $wpdb;
        $table = $this->getTableName();
        
        $now = current_time('mysql', true);
        
        $insert_data = [
            'idempotency_key' => $data['idempotency_key'],
            'phone_hash'      => $data['phone_hash'],
            'phone_masked'    => $data['phone_masked'],
            'sender'          => $data['sender'],
            'payment_type'    => $data['payment_type'],
            'source'          => $data['source'],
            'source_id'       => (string) $data['source_id'],
            'source_event'    => $data['source_event'],
            'recipient_type'  => $data['recipient_type'],
            'message_excerpt' => substr($data['message'] ?? '', 0, 150),
            'message_text'    => $data['message'] ?? '',
            'status'          => 'processing',
            'locked_at'       => $now,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];

        // wpdb->insert returns false if insertion fails (e.g. duplicate key or schema error)
        $suppress = $wpdb->suppress_errors(true);
        $result = $wpdb->insert($table, $insert_data);
        $last_error = $wpdb->last_error;
        $wpdb->suppress_errors($suppress);

        if ($result === false) {
            if (!empty($last_error) && stripos($last_error, 'Duplicate entry') === false) {
                // It's a real database error (e.g. missing column), not a race condition
                error_log('Ersaal DB Insert Failed: ' . $last_error);
                throw new \RuntimeException('Unable to create message log.');
            }
            return false;
        }

        return true;
    }

    /**
     * Atomically acquire a scheduled retry log for processing.
     */
    public function acquireForRetry(string $idempotency_key): bool
    {
        global $wpdb;
        $table = $this->getTableName();
        
        $now = current_time('mysql', true);
        
        $sql = $wpdb->prepare(
            "UPDATE {$table} 
            SET status = 'processing', locked_at = %s, updated_at = %s 
            WHERE idempotency_key = %s 
            AND status = 'retry_scheduled' 
            AND next_retry_at <= %s 
            AND (locked_at IS NULL OR locked_at < DATE_SUB(%s, INTERVAL 10 MINUTE))",
            $now, $now, $idempotency_key, $now, $now
        );
        
        $rows_affected = $wpdb->query($sql);
        
        return $rows_affected > 0;
    }
    
    public function incrementAttempts(string $idempotency_key): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $now = current_time('mysql', true);
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET attempts = attempts + 1, last_attempt_at = %s, updated_at = %s WHERE idempotency_key = %s",
            $now, $now, $idempotency_key
        ));
    }

    public function markAccepted(string $idempotency_key, ?string $message_id, ?int $parts, ?float $cost): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $now = current_time('mysql', true);
        
        $update_data = [
            'status' => 'accepted',
            'locked_at' => null,
            'updated_at' => $now
        ];

        if ($message_id !== null) {
            $update_data['message_id'] = $message_id;
        }
        if ($parts !== null) {
            $update_data['parts_final'] = $parts;
        }
        if ($cost !== null) {
            $update_data['cost_final'] = $cost;
        }
        
        $wpdb->update(
            $table,
            $update_data,
            ['idempotency_key' => $idempotency_key]
        );
    }

    public function markRetryScheduled(string $idempotency_key, int $delay_seconds, ?int $api_code = null, ?string $error_msg = null): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $now = current_time('mysql', true);
        
        $next = gmdate('Y-m-d H:i:s', time() + $delay_seconds);
        
        $update_data = [
            'status' => 'retry_scheduled',
            'next_retry_at' => $next,
            'locked_at' => null,
            'updated_at' => $now
        ];
        
        if ($api_code !== null) {
            $update_data['api_http_code'] = $api_code;
        }
        if ($error_msg !== null) {
            $update_data['api_error'] = substr($error_msg, 0, 255);
        }
        
        $wpdb->update($table, $update_data, ['idempotency_key' => $idempotency_key]);
    }

    public function markFailed(string $idempotency_key, string $finalStatus, ?int $api_code = null, ?string $error_msg = null): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $now = current_time('mysql', true);
        
        $update_data = [
            'status' => $finalStatus,
            'locked_at' => null,
            'updated_at' => $now
        ];
        
        if ($api_code !== null) {
            $update_data['api_http_code'] = $api_code;
        }
        if ($error_msg !== null) {
            $update_data['api_error'] = substr($error_msg, 0, 255);
        }
        
        $wpdb->update($table, $update_data, ['idempotency_key' => $idempotency_key]);
    }

    public function releaseLock(string $idempotency_key): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $wpdb->update(
            $table,
            ['locked_at' => null, 'updated_at' => current_time('mysql', true)],
            ['idempotency_key' => $idempotency_key]
        );
    }

    public function updateErsaalStatus(string $message_id, string $ersaal_status): void
    {
        global $wpdb;
        $table = $this->getTableName();
        $wpdb->update(
            $table,
            ['ersaal_status' => $ersaal_status, 'updated_at' => current_time('mysql', true)],
            ['message_id' => $message_id]
        );
    }

    public function getLogsStats(): array
    {
        global $wpdb;
        $table = $this->getTableName();
        
        $results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$table} GROUP BY status", ARRAY_A);
        
        $stats = [
            'total' => 0,
            'processing' => 0,
            'accepted' => 0,
            'retry_scheduled' => 0,
            'failed' => 0,
            'error' => 0
        ];
        
        if ($results) {
            foreach ($results as $row) {
                $status = $row['status'];
                $count = (int)$row['count'];
                $stats['total'] += $count;
                if (isset($stats[$status])) {
                    $stats[$status] = $count;
                }
            }
        }
        
        return $stats;
    }

    public function getDashboardStats(): array
    {
        global $wpdb;
        $table = $this->getTableName();
        
        $today = current_time('Y-m-d');
        
        $stats = [
            'total_today' => 0,
            'accepted_today' => 0,
            'failed_today' => 0,
            'retry_today' => 0,
        ];
        
        $sql = $wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM {$table} WHERE DATE(created_at) = %s GROUP BY status",
            $today
        );
        
        $results = $wpdb->get_results($sql);
        
        if ($results) {
            foreach ($results as $row) {
                $count = (int)$row->count;
                $stats['total_today'] += $count;
                if (in_array($row->status, ['accepted', 'delivered'], true)) {
                    $stats['accepted_today'] += $count;
                } elseif (in_array($row->status, ['failed', 'error', 'rejected'], true)) {
                    $stats['failed_today'] += $count;
                } elseif (in_array($row->status, ['retry_scheduled', 'processing'], true)) {
                    $stats['retry_today'] += $count;
                }
            }
        }
        
        return $stats;
    }

    public function getLogs(array $args = []): array
    {
        global $wpdb;
        $table = $this->getTableName();
        
        $page = isset($args['page']) ? max(1, (int)$args['page']) : 1;
        $perPage = isset($args['per_page']) ? (int)$args['per_page'] : 20;
        if ($perPage === 0) $perPage = 20;

        $status = isset($args['status']) ? sanitize_text_field($args['status']) : '';
        $source = isset($args['source']) ? sanitize_text_field($args['source']) : '';
        $event = isset($args['event']) ? sanitize_text_field($args['event']) : '';
        $dateFrom = isset($args['date_from']) ? sanitize_text_field($args['date_from']) : '';
        $dateTo = isset($args['date_to']) ? sanitize_text_field($args['date_to']) : '';
        $search = isset($args['search']) ? sanitize_text_field($args['search']) : '';
        $orderby = isset($args['orderby']) ? sanitize_text_field($args['orderby']) : 'created_at';
        $order = isset($args['order']) ? strtoupper(sanitize_text_field($args['order'])) : 'DESC';
        
        $where = ["1=1"];
        $params = [];
        
        if ($status && $status !== 'all') {
            $where[] = "status = %s";
            $params[] = $status;
        }
        
        if ($source && $source !== 'all') {
            $where[] = "source = %s";
            $params[] = $source;
        }

        if ($event && $event !== 'all') {
            $where[] = "source_event = %s";
            $params[] = $event;
        }

        if ($dateFrom && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = "created_at >= %s";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = "created_at <= %s";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(id = %d OR message_id LIKE %s OR source_id LIKE %s OR phone_masked LIKE %s)";
            $params[] = is_numeric($search) ? (int)$search : 0;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // whitelist orderby
        $allowed_orderby = ['id', 'created_at', 'status'];
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'created_at';
        }
        
        $order = ($order === 'ASC') ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;
        
        if ($perPage > 0) {
            $limit_sql = "LIMIT %d OFFSET %d";
            $query_params = array_merge($params, [$perPage, $offset]);
        } else {
            // -1 for all
            $limit_sql = "";
            $query_params = $params;
        }
        
        if (!empty($query_params)) {
            $query = $wpdb->prepare("SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}", ...$query_params);
        } else {
            $query = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} {$limit_sql}";
        }
        
        if (!empty($params)) {
            $total_query = $wpdb->prepare("SELECT COUNT(id) FROM {$table} WHERE {$where_sql}", ...$params);
        } else {
            $total_query = "SELECT COUNT(id) FROM {$table} WHERE {$where_sql}";
        }
        
        $items = $wpdb->get_results($query);
        $total_items = (int)$wpdb->get_var($total_query);
        
        return [
            'items' => $items,
            'total' => $total_items,
            'pages' => $perPage > 0 ? ceil($total_items / $perPage) : 1,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    public function deleteById(int $id): bool
    {
        global $wpdb;
        $table = $this->getTableName();
        $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);
        return $deleted !== false && $deleted > 0;
    }

    public function deleteBulk(array $ids): int
    {
        global $wpdb;
        if (empty($ids)) {
            return 0;
        }

        $table = $this->getTableName();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        $sql = $wpdb->prepare("DELETE FROM {$table} WHERE id IN ($placeholders)", ...$ids);
        $deleted = $wpdb->query($sql);
        
        return $deleted !== false ? $deleted : 0;
    }

    public function getDistinctEvents(): array
    {
        global $wpdb;
        $table = $this->getTableName();
        $results = $wpdb->get_col("SELECT DISTINCT source_event FROM {$table} WHERE source_event != '' AND source_event IS NOT NULL ORDER BY source_event ASC");
        return $results ?: [];
    }
}
