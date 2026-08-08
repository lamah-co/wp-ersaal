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
        <div class="wrap ersaal-admin ersaal-page ersaal-page-narrow">
            <div class="ersaal-page-header">
                <div>
                    <h1 class="ersaal-page-title"><?php esc_html_e('Ersaal Settings', 'ersaal'); ?></h1>
                    <p class="ersaal-page-description"><?php esc_html_e('Manage API connection, defaults, and system preferences.', 'ersaal'); ?></p>
                </div>
                <div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#connection-account')); ?>" class="ersaal-btn ersaal-btn-ghost">
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php esc_html_e('Need help?', 'ersaal'); ?>
                    </a>
                </div>
            </div>

            <div class="ersaal-tabs-list" role="tablist">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?page=ersaal-settings&tab=<?php echo esc_attr($tab_id); ?>" class="ersaal-tab <?php echo $active_tab === $tab_id ? 'ersaal-tab-active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === $tab_id ? 'true' : 'false'; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="ersaal-settings-content ersaal-card">
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
