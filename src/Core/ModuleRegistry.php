<?php
declare(strict_types=1);

namespace Ersaal\Core;

use Ersaal\Contracts\IntegrationInterface;
use Ersaal\Contracts\ModuleInterface;

class ModuleRegistry
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];
    
    /** @var array<string, IntegrationInterface> */
    private array $integrations = [];

    public function registerModule(ModuleInterface $module): void
    {
        $this->modules[$module->id()] = $module;
        
        if ($module->isActive()) {
            $module->register();
        }
    }

    public function registerIntegration(IntegrationInterface $integration): void
    {
        $this->integrations[$integration->id()] = $integration;

        if ($integration->isAvailable()) {
            foreach ($integration->requires() as $req) {
                if (!isset($this->modules[$req]) || !$this->modules[$req]->isActive()) {
                    return; // Dependency not met
                }
            }
            $integration->register();
        }
    }

    public function boot(): void
    {
        foreach ($this->modules as $module) {
            if ($module->isActive()) {
                $module->boot();
            }
        }

        foreach ($this->integrations as $integration) {
            if ($integration->isAvailable()) {
                $canBoot = true;
                foreach ($integration->requires() as $req) {
                    if (!isset($this->modules[$req]) || !$this->modules[$req]->isActive()) {
                        $canBoot = false;
                        break;
                    }
                }
                
                if ($canBoot) {
                    $integration->boot();
                }
            }
        }
    }
}
