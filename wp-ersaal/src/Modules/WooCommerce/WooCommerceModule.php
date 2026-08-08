<?php
declare(strict_types=1);

namespace Ersaal\Modules\WooCommerce;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Services\MessageService;

class WooCommerceModule implements ModuleInterface
{
    private Options $options;
    private MessageService $messageService;
    private WooCommerceSettings $settings;
    private OrderEventHandler $eventHandler;
    private ManualOrderSmsBox $manualBox;

    public function __construct(Options $options, MessageService $messageService)
    {
        $this->options = $options;
        $this->messageService = $messageService;
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
        
        $this->eventHandler = new OrderEventHandler($this->options, $this->messageService);
        $this->eventHandler->register();
        
        $this->manualBox = new ManualOrderSmsBox($this->options, $this->messageService);
        $this->manualBox->register();
    }

    public function boot(): void
    {
    }
}
