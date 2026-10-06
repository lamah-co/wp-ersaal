<?php
declare(strict_types=1);

namespace Ersaal\Modules\ManualSend;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Services\MessageService;
use Ersaal\Storage\LogRepository;
use Ersaal\API\Client;

class ManualSendModule implements ModuleInterface
{
    private Options $options;
    private MessageService $messageService;
    private LogRepository $repository;
    private Client $client;

    public function __construct(
        Options $options,
        MessageService $messageService,
        ?LogRepository $repository = null,
        ?Client $client = null
    ) {
        $this->options        = $options;
        $this->messageService = $messageService;
        $this->repository     = $repository ?? new LogRepository();
        $this->client         = $client ?? new Client($options);
    }

    public function id(): string
    {
        return 'manual_send';
    }

    public function isActive(): bool
    {
        return true;
    }

    public function register(): void
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addAdminMenu'], 30);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
            
            $handler = new ManualSendHandler($this->messageService, $this->repository, $this->client);
            add_action('wp_ajax_ersaal_manual_send', [$handler, 'handleRequest']);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('Send SMS', 'ersaal'),
            __('Send SMS', 'ersaal'),
            'manage_options',
            'ersaal-send-message',
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        require_once ERSAAL_PLUGIN_DIR . 'admin/ManualSendPage.php';
        $page = new \Ersaal\Admin\ManualSendPage($this->options);
        $page->render();
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'ersaal-send-message') === false) {
            return;
        }

        wp_enqueue_script(
            'ersaal-manual-send',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/manual-send.js',
            ['jquery'],
            ERSAAL_VERSION,
            true
        );

        wp_localize_script('ersaal-manual-send', 'ersaalManualSend', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ersaal_manual_send'),
            'i18n'     => [
                'sending' => __('Sending...', 'ersaal'),
                'send'    => __('Send SMS', 'ersaal'),
                'error'   => __('An unexpected error occurred.', 'ersaal'),
                'success' => __('Message accepted by Ersaal.', 'ersaal'),
                'error_label' => __('Message could not be sent.', 'ersaal')
            ]
        ]);
    }
}
