<?php
declare(strict_types=1);

namespace Ersaal\Jobs;

use Ersaal\Storage\LogRepository;
use Ersaal\API\Client;
use Ersaal\API\Exceptions\RateLimitException;
use Ersaal\API\Exceptions\ServerException;
use Ersaal\API\Exceptions\ConnectionException;

class MessageJob
{
    private LogRepository $repository;
    private Client $client;

    public function __construct(LogRepository $repository, Client $client)
    {
        $this->repository = $repository;
        $this->client = $client;
    }

    public function handle(string $idempotencyKey, array $payload): void
    {
        $log = $this->repository->getLogByKey($idempotencyKey);
        
        if (!$log) {
            return;
        }

        // If it's a retry, we need to acquire lock.
        // But if it's the first attempt (status = processing and attempts = 0), it's already locked via createProcessingLog.
        if ($log->status === 'retry_scheduled') {
            $locked = $this->repository->acquireForRetry($idempotencyKey);
            if (!$locked) {
                return;
            }
        } elseif ($log->status === 'processing' && $log->attempts > 0) {
            // Processing and attempts > 0 means it might be stuck, but acquireForRetry is for retry_scheduled
            // Let's release lock or ignore if it's not retry_scheduled unless it's a fresh creation
            // The job shouldn't hit here for a stuck processing unless we have a specific recovery cron.
            // For now, if it's processing, we assume we hold the lock (just created).
        } elseif ($log->status !== 'processing') {
            return; // Accepted, Failed, Error, etc.
        }

        $this->repository->incrementAttempts($idempotencyKey);
        // Refresh log to get updated attempts
        $log = $this->repository->getLogByKey($idempotencyKey);

        try {
            // Only send API-expected fields to Ersaal (exclude internal fields like source)
            $apiPayload = [
                'receiver'     => $payload['receiver'],
                'message'      => $payload['message'],
                'sender'       => $payload['sender'] ?? '',
                'payment_type' => $payload['payment_type'] ?? 'wallet',
            ];

            $response = $this->client->sendMessage($apiPayload, $idempotencyKey);
            $data = $response->getData();
            
            // Try multiple response paths for message_id
            $messageId = $data['data']['message_id']
                ?? $data['data']['id']
                ?? $data['message_id']
                ?? $data['id']
                ?? null;
            
            // Only use real values from API, null if not present
            $parts = isset($data['data']['parts']) ? (int) $data['data']['parts']
                : (isset($data['parts']) ? (int) $data['parts'] : null);
            
            $cost = isset($data['data']['cost']) ? (float) $data['data']['cost']
                : (isset($data['cost']) ? (float) $data['cost'] : null);
            
            $this->repository->markAccepted(
                $idempotencyKey,
                $messageId,
                $parts,
                $cost
            );
            
            $note = $messageId ? sprintf(__('Ersaal SMS accepted. Message ID: %s', 'ersaal'), $messageId) : __('Ersaal SMS accepted.', 'ersaal');
            $this->addOrderNote($idempotencyKey, $note);
            
            do_action('ersaal_message_accepted', $idempotencyKey, $messageId);

        } catch (\Ersaal\API\Exceptions\ValidationException $e) {
            $this->repository->markFailed($idempotencyKey, 'failed', $e->getCode(), $e->getMessage());
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
        } catch (\Ersaal\API\Exceptions\AuthenticationException $e) {
            $this->repository->markFailed($idempotencyKey, 'failed', $e->getCode(), $e->getMessage());
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
        } catch (\Ersaal\API\Exceptions\BalanceException $e) {
            $this->repository->markFailed($idempotencyKey, 'failed', $e->getCode(), $e->getMessage());
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
        } catch (\Ersaal\API\Exceptions\RateLimitException $e) {
            $this->handleRetryableError($log, $idempotencyKey, $payload, $e->getRetryAfterSeconds(), $e);
        } catch (\Ersaal\API\Exceptions\ConnectionException | \Ersaal\API\Exceptions\ServerException $e) {
            $this->handleRetryableError($log, $idempotencyKey, $payload, 60, $e);
        } catch (\Throwable $e) {
            // Unexpected errors, invalid JSON, internal bugs -> error
            $this->repository->markFailed(
                $idempotencyKey,
                'error',
                $e->getCode() ?: 0,
                sprintf(__('Unexpected internal error: %s', 'ersaal'), $e->getMessage())
            );
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
        }
    }

    private function handleRetryableError($log, string $idempotencyKey, array $payload, int $retryAfter, \Throwable $e): void
    {
        $source = sanitize_key(
            $payload['source']
            ?? (is_array($log) ? ($log['source'] ?? '') : ($log->source ?? ''))
        );
        
        // Manual sends do not get retried (no state to reconstruct)
        if ($source === 'manual') {
            $this->repository->markFailed(
                $idempotencyKey, 
                'error', 
                (int) $e->getCode(), 
                $this->sanitizeError($e->getMessage())
            );
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
            return;
        }

        if ($log->attempts < 3) {
            $this->repository->markRetryScheduled($idempotencyKey, $retryAfter, $e->getCode() ?: 0, $e->getMessage());

            $args = ['idempotency_key' => $idempotencyKey, 'payload' => $payload];
            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(time() + $retryAfter, 'ersaal_process_message_job', $args, 'ersaal');
            } else {
                wp_schedule_single_event(time() + $retryAfter, 'ersaal_process_message_job', $args);
            }
        } else {
            // Max attempts reached, fails permanently as error
            $this->repository->markFailed($idempotencyKey, 'error', $e->getCode() ?: 0, $e->getMessage());
            $this->notifyFailure($idempotencyKey, (int) $e->getCode(), $e->getMessage());
        }
    }

    private function sanitizeError(string $message): string
    {
        $clean = sanitize_text_field($message);
        if (mb_strlen($clean) > 255) {
            $clean = mb_substr($clean, 0, 252) . '...';
        }
        return $clean;
    }

    private function addOrderNote(string $idempotencyKey, string $note): void
    {
        $log = $this->repository->getLogByKey($idempotencyKey);
        if (!$log || empty($log->source_id) || $log->source !== 'woocommerce') {
            return;
        }

        if (function_exists('wc_get_order')) {
            $order = wc_get_order($log->source_id);
            if ($order) {
                $order->add_order_note($note);
            }
        }
    }

    private function notifyFailure(string $idempotencyKey, int $code, string $message): void
    {
        $note = sprintf(
            __('Ersaal SMS failed: %s', 'ersaal'),
            $this->sanitizeError($message)
        );

        do_action('ersaal_message_failed', $idempotencyKey, $code, $note);
        $this->addOrderNote($idempotencyKey, $note);
    }
}
