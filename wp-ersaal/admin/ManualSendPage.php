<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Core\Options;
use Ersaal\API\Client;
use Ersaal\API\Exceptions\ApiException;

class ManualSendPage
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function render(): void
    {
        $status = $this->getConnectionStatus();
        require ERSAAL_PLUGIN_DIR . 'admin/views/manual-send.php';
    }

    private function getConnectionStatus(): array
    {
        $client = new Client($this->options);
        
        try {
            $details = $client->getProjectDetails();
            $data = $details->getData();
            
            $projectName = $data['project_name'] ?? __('Unknown Project', 'ersaal');
            $projectStatus = $data['status'] ?? 'unknown';
            $subscriptions = $data['subscription'] ?? [];
            
            // Try to get balance
            $walletBalance = __('Unknown', 'ersaal');
            $smsBalance = 0;
            try {
                $balanceResponse = $client->getBalance();
                $bData = $balanceResponse->getData();
                if (isset($bData['SMS']['balance'])) {
                    $smsBalance = $bData['SMS']['balance'];
                }
            } catch (\Throwable $e) {
                // Ignore balance errors, maybe not supported
            }
            
            return [
                'connected' => true,
                'project_name' => $projectName,
                'status' => $projectStatus,
                'balance' => $walletBalance,
                'sms_balance' => $smsBalance,
                'subscriptions' => $subscriptions,
                'error' => ''
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'project_name' => '',
                'status' => '',
                'balance' => __('Unknown', 'ersaal'),
                'sms_balance' => 0,
                'subscriptions' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
