<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\API\Client;
use Ersaal\API\Response;

final class OTPClient
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function initiate(array $payload, string $idempotencyKey): Response
    {
        return $this->client->initiateOtp($payload, $idempotencyKey);
    }

    public function verify(string $reference, string $code): Response
    {
        return $this->client->verifyOtp($reference, $code);
    }
}
