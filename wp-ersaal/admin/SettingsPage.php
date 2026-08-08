<?php
declare(strict_types=1);

namespace Ersaal\Admin;

use Ersaal\Core\Options;
use Ersaal\Modules\Settings\SystemStatus;

class SettingsPage
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function render(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        $tabs = [
            'general' => __('General Settings', 'ersaal'),
            'advanced' => __('Advanced', 'ersaal'),
            'status' => __('System Status', 'ersaal')
        ];

        $tabs = apply_filters('ersaal_settings_tabs', $tabs);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ersaal Settings', 'ersaal'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?page=ersaal-settings&tab=<?php echo esc_attr($tab_id); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <div class="ersaal-settings-content" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <?php
                if ($active_tab === 'general') {
                    $this->renderGeneral();
                } elseif ($active_tab === 'advanced') {
                    $this->renderAdvanced();
                } elseif ($active_tab === 'status') {
                    $this->renderStatus();
                } else {
                    do_action('ersaal_render_settings_tab_' . $active_tab);
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function renderGeneral(): void
    {
        require ERSAAL_PLUGIN_DIR . 'admin/views/settings-general.php';
    }

    private function renderAdvanced(): void
    {
        require ERSAAL_PLUGIN_DIR . 'admin/views/settings-advanced.php';
    }

    private function renderStatus(): void
    {
        $status = SystemStatus::getStatus();
        require ERSAAL_PLUGIN_DIR . 'admin/views/system-status.php';
    }
}
