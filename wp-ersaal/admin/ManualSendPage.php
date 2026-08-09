<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Core\Options;
use Ersaal\Services\ConnectionStatusService;

class ManualSendPage
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function render(): void
    {
        $status = (new ConnectionStatusService($this->options))->getStatus();
        require ERSAAL_PLUGIN_DIR . 'admin/views/manual-send.php';
    }
}
