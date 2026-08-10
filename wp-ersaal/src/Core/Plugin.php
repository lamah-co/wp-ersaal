<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Plugin
{
    private ModuleRegistry $registry;
    private Options $options;
    private ?\Ersaal\Modules\OTP\OTPService $otpService = null;
    
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
        
        $messagingModule = new \Ersaal\Modules\Messaging\MessagingModule($this->options);
        $this->registry->registerModule($messagingModule);

        $otpModule = new \Ersaal\Modules\OTP\OTPModule($this->options);
        $this->otpService = $otpModule->getService();
        $this->registry->registerModule($otpModule);
        
        $messageService = new \Ersaal\Services\MessageService(new \Ersaal\Storage\LogRepository());
        $this->registry->registerModule(new \Ersaal\Modules\ManualSend\ManualSendModule($this->options, $messageService));
        
        $this->registry->registerModule(new \Ersaal\Modules\WooCommerce\WooCommerceModule($this->options, $messageService));
        $this->registry->registerModule(new \Ersaal\Modules\Help\HelpModule($this->options));
        
        $this->registry->boot();
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'ersaal',
            false,
            dirname(plugin_basename(ERSAAL_PLUGIN_FILE)) . '/languages'
        );
    }
    
    public function enqueueGlobalAdminAssets(string $hook): void
    {
        $isErsaalPage = strpos($hook, 'ersaal') !== false;
        $isWooCommerceSettings = ($hook === 'woocommerce_page_wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'ersaal');
        
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
}
