<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Storage\LogRepository;
use Ersaal\Core\Options;
use Ersaal\Services\ConnectionStatusService;
use Ersaal\Modules\OTP\OTPLogRepository;

class DashboardPage
{
    private LogRepository $repo;
    private OTPLogRepository $otpRepo;
    private Options $options;

    public function __construct(LogRepository $repo, OTPLogRepository $otpRepo, Options $options)
    {
        $this->repo = $repo;
        $this->otpRepo = $otpRepo;
        $this->options = $options;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'ersaal'));
        }

        $stats = $this->repo->getDashboardStats();
        $recent = $this->repo->getLogs([
            'per_page' => 10,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ]);
        $connection = (new ConnectionStatusService($this->options))->getStatus();
        $otpStats = $this->otpRepo->getStatsToday();
        $recentOtp = $this->otpRepo->getRecent(6);

        require ERSAAL_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
}
