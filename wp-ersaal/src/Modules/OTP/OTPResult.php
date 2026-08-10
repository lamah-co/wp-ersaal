<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

final class OTPResult
{
    private bool $success;
    private string $status;
    private ?string $reference;
    /** @var mixed */
    private $cost;
    private ?int $expiresIn;
    private string $errorCode;
    private string $errorMessage;
    private int $httpStatus;
    private ?int $retryAfter;

    /** @param mixed $cost */
    private function __construct(
        bool $success,
        string $status,
        ?string $reference,
        $cost,
        ?int $expiresIn,
        string $errorCode,
        string $errorMessage,
        int $httpStatus,
        ?int $retryAfter
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->reference = $reference;
        $this->cost = $cost;
        $this->expiresIn = $expiresIn;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->httpStatus = $httpStatus;
        $this->retryAfter = $retryAfter;
    }

    /** @param mixed $cost */
    public static function sent(string $reference, $cost, ?int $expiresIn): self
    {
        return new self(true, 'sent', $reference, $cost, $expiresIn, '', '', 200, null);
    }

    public static function verified(): self
    {
        return new self(true, 'verified', null, null, null, '', '', 200, null);
    }

    public static function failed(string $status, string $errorCode, string $errorMessage, int $httpStatus = 0, ?int $retryAfter = null): self
    {
        return new self(false, $status, null, null, null, $errorCode, $errorMessage, $httpStatus, $retryAfter);
    }

    public function isSuccess(): bool { return $this->success; }
    public function getStatus(): string { return $this->status; }
    public function getReference(): ?string { return $this->reference; }
    /** @return mixed */
    public function getCost() { return $this->cost; }
    public function getExpiresIn(): ?int { return $this->expiresIn; }
    public function getErrorCode(): string { return $this->errorCode; }
    public function getErrorMessage(): string { return $this->errorMessage; }
    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getRetryAfter(): ?int { return $this->retryAfter; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'reference' => $this->reference,
            'cost' => $this->cost,
            'expires_in' => $this->expiresIn,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'http_status' => $this->httpStatus,
            'retry_after' => $this->retryAfter,
        ];
    }
}
