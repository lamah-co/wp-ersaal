<?php
declare(strict_types=1);

namespace Ersaal\Modules\ManualSend;

use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;
use Ersaal\Services\MessageService;

class ManualSendModule implements ModuleInterface
{
    private Options $options;
    private MessageService $messageService;

    public function __construct(Options $options, MessageService $messageService)
    {
        $this->options = $options;
        $this->messageService = $messageService;
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
            
            $handler = new ManualSendHandler($this->messageService);
            add_action('wp_ajax_ersaal_manual_send', [$handler, 'handleRequest']);
        }
    }

    public function boot(): void
    {
    }

    public function addAdminMenu(): void
    {
        add_submenu_page(
            'ersaal-settings',
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

        wp_enqueue_style(
            'ersaal-manual-send',
            ERSAAL_PLUGIN_URL . 'admin/assets/css/manual-send.css',
            [],
            ERSAAL_VERSION
        );

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
                'send'    => __('Send Message', 'ersaal'),
                'error'   => __('An unexpected error occurred.', 'ersaal'),
                'success' => __('Message accepted by Ersaal.', 'ersaal')
            ]
        ]);
    }
}
