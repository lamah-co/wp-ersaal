<?php
declare(strict_types=1);

namespace Ersaal\Modules\Messaging;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Storage\LogRepository;
use Ersaal\API\Client;
use Ersaal\Jobs\MessageJob;

class MessagingModule implements ModuleInterface
{
    private Options $options;
    private LogRepository $repository;
    private Client $client;

    public function __construct(Options $options, LogRepository $repository = null, Client $client = null)
    {
        $this->options = $options;
        $this->repository = $repository ?? new LogRepository();
        $this->client = $client ?? new Client($options);
    }

    public function id(): string
    {
        return 'messaging';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function register(): void
    {
        add_action('ersaal_process_message_job', [$this, 'processMessageJob'], 10, 2);
    }

    public function boot(): void
    {
    }

    public function processMessageJob(string $idempotencyKey, array $payload): void
    {
        $job = new MessageJob($this->repository, $this->client);
        $job->handle($idempotencyKey, $payload);
    }
}
