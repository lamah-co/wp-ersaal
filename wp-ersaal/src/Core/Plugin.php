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
    
    public function getOptions(): Options
    {
        return $this->options;
    }
    
    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }
}
