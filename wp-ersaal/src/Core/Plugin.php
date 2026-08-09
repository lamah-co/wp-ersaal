<?php
declare(strict_types=1);

namespace Ersaal\Core;

class Plugin
{
    private ModuleRegistry $registry;
    private Options $options;
    
    public function __construct()
    {
        $this->registry = new ModuleRegistry();
        $this->options = new Options();
    }
    
    public function boot(): void
    {
        // Automatically check and run schema upgrades on boot if outdated (no need for reactivation)
        add_action('plugins_loaded', function () {
            if (class_exists(\Ersaal\Core\Database::class)) {
                (new \Ersaal\Core\Database())->upgrade();
            }
        });

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueGlobalAdminAssets']);
        }
        
        $this->registry->registerModule(new \Ersaal\Modules\Dashboard\DashboardModule($this->options));
        $this->registry->registerModule(new \Ersaal\Modules\Settings\SettingsModule($this->options));
        $this->registry->registerModule(new \Ersaal\Modules\Logs\LogsModule($this->options));
        
        $messagingModule = new \Ersaal\Modules\Messaging\MessagingModule($this->options);
        $this->registry->registerModule($messagingModule);
        
        $messageService = new \Ersaal\Services\MessageService(new \Ersaal\Storage\LogRepository());
        $this->registry->registerModule(new \Ersaal\Modules\ManualSend\ManualSendModule($this->options, $messageService));
        
        $this->registry->registerModule(new \Ersaal\Modules\WooCommerce\WooCommerceModule($this->options, $messageService));
        $this->registry->registerModule(new \Ersaal\Modules\Help\HelpModule($this->options));
        
        $this->registry->boot();
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
}
