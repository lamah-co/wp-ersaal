<?php
declare(strict_types=1);

namespace Ersaal\Contracts;

interface IntegrationInterface
{
    public function id(): string;
    public function requires(): array;
    public function isAvailable(): bool;
    public function register(): void;
    public function boot(): void;
}
