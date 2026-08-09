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

            try {
                $balance = $client->getBalance()->getData();
                if (isset($balance['SMS']['balance'])) {
                    $smsBalance = $balance['SMS']['balance'];
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
                'error' => '',
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'project_name' => '',
                'status' => 'unavailable',
                'subscriptions' => [],
                'sms_balance' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
