<?php
declare(strict_types=1);

namespace Ersaal\Contracts;

interface ModuleInterface
{
    public function id(): string;
    public function register(): void;
    public function boot(): void;
    public function isActive(): bool;
}
