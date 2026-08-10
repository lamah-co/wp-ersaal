<?php
declare(strict_types=1);

namespace Ersaal\Services;

use Ersaal\Storage\LogRepository;

class MessageService
{
    private LogRepository $repository;

    public function __construct(LogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function send(array $data): string
    {
        // 1. Generate or use idempotency key
        $idempotencyKey = $data['idempotency_key'] ?? wp_generate_uuid4();

        $data = apply_filters('ersaal_message_payload', $data);
        $data['receiver'] = (new PhoneValidator())->normalize((string) ($data['receiver'] ?? ''));

        $logData = [
            'idempotency_key' => $idempotencyKey,
            'phone_hash'      => hash('sha256', $data['receiver']),
            'phone_masked'    => $this->maskPhone($data['receiver']),
            'sender'          => $data['sender'] ?? '',
            'payment_type'    => $data['payment_type'] ?? 'wallet',
            'source'          => $data['source'] ?? 'api',
            'source_id'       => $data['source_id'] ?? '',
            'source_event'    => $data['source_event'] ?? '',
            'recipient_type'  => $data['recipient_type'] ?? 'customer',
            'message'         => $data['message'],
        ];

        // 2. Try to create the DB record in processing state
        $created = $this->repository->createProcessingLog($logData);

        if (!$created) {
            // Log already exists (Race Condition or duplicate retry prevention)
            return $idempotencyKey;
        }

        // 3. Queue the job
        $this->enqueueJob($idempotencyKey, $data);

        return $idempotencyKey;
    }

    private function enqueueJob(string $idempotencyKey, array $data): void
    {
        $args = [
            'idempotency_key' => $idempotencyKey,
            'payload' => [
                'receiver'     => $data['receiver'],
                'message'      => $data['message'],
                'sender'       => $data['sender'] ?? null,
                'payment_type' => $data['payment_type'] ?? 'wallet',
                'source'       => $data['source'] ?? 'api',
                'source_id'    => $data['source_id'] ?? '',
            ]
        ];

        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('ersaal_process_message_job', $args, 'ersaal');
        } else {
            // Fallback to WP-Cron
            wp_schedule_single_event(time(), 'ersaal_process_message_job', $args);
        }
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }
        return str_repeat('*', $length - 4) . substr($phone, -4);
    }
}
