<?php
declare(strict_types=1);

namespace Ersaal\Services;

use Ersaal\API\Client;
use Ersaal\Core\Options;

/**
 * Provides read-only project readiness data for admin screens.
 */
final class ConnectionStatusService
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function getStatus(): array
    {
        $client = new Client($this->options);

        try {
            $details = $client->getProjectDetails()->getData();
            $smsBalance = null;
            $otpBalance = null;
            $otpLimit = null;

            try {
                $balance = $client->getBalance()->getData();
                if (isset($balance['SMS']['balance'])) {
                    $smsBalance = $balance['SMS']['balance'];
                }
                $otp = $balance['OTP'] ?? null;
                if (is_array($otp) && array_key_exists('balance', $otp)) {
                    $otpBalance = $otp['balance'];
                }
                if (is_array($otp) && array_key_exists('limit', $otp)) {
                    $otpLimit = $otp['limit'];
                }
            } catch (\Throwable $e) {
                // Balance is optional readiness metadata; connection can still be healthy.
            }

            return [
                'connected' => true,
                'project_name' => $details['project_name'] ?? __('Unknown Project', 'ersaal'),
                'status' => $details['status'] ?? 'unknown',
                'subscriptions' => is_array($details['subscription'] ?? null) ? $details['subscription'] : [],
                'sms_balance' => $smsBalance,
                'otp_balance' => $otpBalance,
                'otp_limit' => $otpLimit,
                'error' => '',
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'project_name' => '',
                'status' => 'unavailable',
                'subscriptions' => [],
                'sms_balance' => null,
                'otp_balance' => null,
                'otp_limit' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
