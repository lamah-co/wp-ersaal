<?php
declare(strict_types=1);

namespace Ersaal\PublicApi;

final class SmsResult
{
    private bool $success;
    private string $status;
    private ?int $logId;
    private ?string $messageId;
    private string $idempotencyKey;
    private string $errorCode;
    private string $errorMessage;

    private function __construct(
        bool $success,
        string $status,
        ?int $logId,
        ?string $messageId,
        string $idempotencyKey,
        string $errorCode,
        string $errorMessage
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->logId = $logId;
        $this->messageId = $messageId;
        $this->idempotencyKey = $idempotencyKey;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public static function fromLog(object $log, string $idempotencyKey): self
    {
        $status = sanitize_key((string) ($log->status ?? 'processing'));
        $success = !in_array($status, ['failed', 'error', 'rejected'], true);

        return new self(
            $success,
            $status,
            isset($log->id) ? (int) $log->id : null,
            !empty($log->message_id) ? sanitize_text_field((string) $log->message_id) : null,
            $idempotencyKey,
            $success ? '' : 'existing_failed_request',
            $success ? '' : sanitize_text_field((string) ($log->api_error ?? ''))
        );
    }

    public static function failure(
        string $status,
        string $errorCode,
        string $errorMessage,
        string $idempotencyKey = '',
        ?int $logId = null
    ): self {
        return new self(
            false,
            sanitize_key($status),
            $logId,
            null,
            $idempotencyKey,
            sanitize_key($errorCode),
            sanitize_text_field($errorMessage)
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLogId(): ?int
    {
        return $this->logId;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'log_id' => $this->logId,
            'message_id' => $this->messageId,
            'idempotency_key' => $this->idempotencyKey,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
