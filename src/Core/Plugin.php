<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Plugin
{
    private ModuleRegistry $registry;
    private Options $options;
    private ?\Ersaal\Modules\OTP\OTPService $otpService = null;
    private ?\Ersaal\PublicApi\SmsFacade $smsFacade = null;
    
    public function __construct()
    {
        $this->registry = new ModuleRegistry();
        $this->options = new Options();
    }
    
    public function boot(): void
    {
        add_action('init', [$this, 'loadTextdomain'], 0);

        // Run versioned migrations during normal plugin boot. Database::upgrade()
        // skips dbDelta when the installed schema is already current.
        (new Database())->upgrade();

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueGlobalAdminAssets']);
        }
        
        $this->registry->registerModule(new \Ersaal\Modules\Dashboard\DashboardModule($this->options));
        $this->registry->registerModule(new \Ersaal\Modules\Settings\SettingsModule($this->options));
        $this->registry->registerModule(new \Ersaal\Modules\Logs\LogsModule($this->options));
        
        $logRepository = new \Ersaal\Storage\LogRepository();
        $messageService = new \Ersaal\Services\MessageService($logRepository);
        $this->smsFacade = new \Ersaal\PublicApi\SmsFacade(
            $this->options,
            $messageService,
            $logRepository
        );

        $messagingModule = new \Ersaal\Modules\Messaging\MessagingModule($this->options, $logRepository);
        $this->registry->registerModule($messagingModule);

        $otpModule = new \Ersaal\Modules\OTP\OTPModule($this->options);
        $this->otpService = $otpModule->getService();
        $this->registry->registerModule($otpModule);
        
        $client = new \Ersaal\API\Client($this->options);
        $this->registry->registerModule(new \Ersaal\Modules\ManualSend\ManualSendModule($this->options, $messageService, $logRepository, $client));
        
        $this->registry->registerModule(new \Ersaal\Modules\WooCommerce\WooCommerceModule($this->options, $messageService, $logRepository));
        $this->registry->registerModule(new \Ersaal\Modules\Help\HelpModule($this->options));
        
        $this->registry->boot();

        // ── Log Retention Cron (#17) ─────────────────────────────────────────
        // Schedule the daily cleanup event once; the callback reads the option
        // at runtime so changes in settings take effect the next day.
        if (!wp_next_scheduled('ersaal_daily_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ersaal_daily_log_cleanup');
        }
        add_action('ersaal_daily_log_cleanup', [$this, 'runLogCleanup']);
    }

    /**
     * WP-Cron callback: delete log rows older than the configured retention period.
     * Skips silently when retention is disabled (0) or the option is not set.
     */
    public function runLogCleanup(): void
    {
        $days = (int) get_option('ersaal_log_retention_days', 0);
        if ($days < 1) {
            return;
        }
        $repo = new \Ersaal\Storage\LogRepository();
        $repo->deleteOlderThan($days);
    }

    public function loadTextdomain(): void
    {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
        load_plugin_textdomain(
            'ersaal',
            false,
            dirname(plugin_basename(ERSAAL_PLUGIN_FILE)) . '/languages'
        );
    }
    
    public function enqueueGlobalAdminAssets(string $hook): void
    {
        $isErsaalPage = strpos($hook, 'ersaal') !== false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $isWooCommerceSettings = ($hook === 'woocommerce_page_wc-settings' && $tab === 'ersaal');
        
        if ($isErsaalPage || $isWooCommerceSettings) {
            wp_enqueue_style(
                'ersaal-tokens',
                ERSAAL_PLUGIN_URL . 'admin/assets/css/ersaal-tokens.css',
                [],
                ERSAAL_VERSION
            );

            wp_enqueue_style(
                'ersaal-admin',
                ERSAAL_PLUGIN_URL . 'admin/assets/css/admin.css',
                ['ersaal-tokens'],
                ERSAAL_VERSION
            );
        }
    }
    
    public function getOptions(): Options
    {
        return $this->options;
    }
    
    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }

    public function getOtpService(): ?\Ersaal\Modules\OTP\OTPService
    {
        return $this->otpService;
    }

    public function getSmsFacade(): ?\Ersaal\PublicApi\SmsFacade
    {
        return $this->smsFacade;
    }
}
