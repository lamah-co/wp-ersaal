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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
        $tabs = [
            'general' => __('General Settings', 'ersaal'),
            'advanced' => __('Advanced', 'ersaal'),
            'status' => __('System Status', 'ersaal')
        ];

        $tabs = apply_filters('ersaal_settings_tabs', $tabs);

        ?>
        <?php $page_width_class = $active_tab === 'woocommerce' ? 'ersaal-page-medium-wide' : 'ersaal-page-medium'; ?>
        <div class="wrap ersaal-admin ersaal-page <?php echo esc_attr($page_width_class); ?>">
            <header class="ersaal-page-header">
                <div class="ersaal-page-header-copy">
                    <h1 class="ersaal-page-title"><?php esc_html_e('Settings', 'ersaal'); ?></h1>
                    <p class="ersaal-page-description"><?php esc_html_e('Manage API connection, defaults, and system preferences.', 'ersaal'); ?></p>
                </div>
                <div class="ersaal-page-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-help#connection-account')); ?>" class="ersaal-btn ersaal-btn-secondary">
                        <?php echo ersaal_admin_icon('help'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php esc_html_e('Need help?', 'ersaal'); ?>
                    </a>
                </div>
            </header>

            <nav class="ersaal-tabs-list" aria-label="<?php esc_attr_e('Settings sections', 'ersaal'); ?>">
                <?php foreach ($tabs as $tab_id => $tab_name): ?>
                    <a href="?page=ersaal-settings&amp;tab=<?php echo esc_attr($tab_id); ?>" class="ersaal-tab <?php echo $active_tab === $tab_id ? 'ersaal-tab-active' : ''; ?>" <?php echo $active_tab === $tab_id ? 'aria-current="page"' : ''; ?>>
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <main class="ersaal-settings-content">
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
            </main>
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
