<?php
declare(strict_types=1);

namespace Ersaal\API\Exceptions;

class RateLimitException extends ApiException
{
    private int $retryAfterSeconds;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, int $retryAfterSeconds = 60)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
