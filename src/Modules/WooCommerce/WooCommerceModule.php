<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Services\MessageService;
use Ersaal\Storage\LogRepository;

class WooCommerceModule implements ModuleInterface
{
    private Options $options;
    private MessageService $messageService;
    private LogRepository $logRepo;
    private WooCommerceSettings $settings;
    private OrderEventHandler $eventHandler;
    private ManualOrderSmsBox $manualBox;

    public function __construct(Options $options, MessageService $messageService, LogRepository $logRepo)
    {
        $this->options = $options;
        $this->messageService = $messageService;
        $this->logRepo = $logRepo;
    }

    public function id(): string
    {
        return 'woocommerce';
    }

    public function isActive(): bool
    {
        return class_exists('WooCommerce');
    }

    public function register(): void
    {
        if (!$this->isActive()) {
            return;
        }
        
        $this->settings = new WooCommerceSettings();
        $this->settings->register();
        
        $this->eventHandler = new OrderEventHandler($this->options, $this->messageService, $this->logRepo);
        $this->eventHandler->register();
        
        $this->manualBox = new ManualOrderSmsBox($this->options, $this->messageService);
        $this->manualBox->register();

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';
        $isWcErsaalTab = ($hook === 'woocommerce_page_wc-settings' && $tab === 'ersaal');
        if (!$isWcErsaalTab) {
            return;
        }

        wp_enqueue_script(
            'ersaal-woocommerce-settings',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/woocommerce-settings.js',
            [],
            ERSAAL_VERSION,
            true
        );

        wp_localize_script('ersaal-woocommerce-settings', 'ersaalWcSettings', [
            'i18n' => [
                'characters'      => __('Characters', 'ersaal-sms-gateway'),
                'parts'           => __('Estimated parts', 'ersaal-sms-gateway'),
                'encoding'        => __('Encoding', 'ersaal-sms-gateway'),
                'empty'           => __('Preview appears as you type.', 'ersaal-sms-gateway'),
                'enabled'         => __('Enabled', 'ersaal-sms-gateway'),
                'disabled'        => __('Disabled', 'ersaal-sms-gateway'),
                'select_template' => __('Focus a message template before inserting a variable.', 'ersaal-sms-gateway'),
            ],
        ]);
    }

    public function boot(): void
    {
    }
}
