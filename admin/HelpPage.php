<?php
declare(strict_types=1);

namespace Ersaal\Admin;

class HelpPage
{
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'ersaal'));
        }

        wp_enqueue_style('ersaal-help-style', ERSAAL_PLUGIN_URL . 'admin/assets/css/help.css', [], ERSAAL_VERSION);
        wp_enqueue_script('ersaal-help-script', ERSAAL_PLUGIN_URL . 'admin/assets/js/help.js', [], ERSAAL_VERSION, true);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $lang_override = isset($_GET['lang']) ? sanitize_key(wp_unslash($_GET['lang'])) : '';

        $locale = get_user_locale();
        $is_arabic = strpos($locale, 'ar') === 0;

        if ($lang_override === 'ar') {
            $is_arabic = true;
        } elseif ($lang_override === 'en') {
            $is_arabic = false;
        }

        wp_localize_script('ersaal-help-script', 'ersaalHelp', [
            'i18n' => [
                'results' => $is_arabic ? 'الأقسام المطابقة: %d' : 'Matching sections: %d',
            ],
        ]);

        if ($is_arabic) {
            $sections = require ERSAAL_PLUGIN_DIR . 'admin/help/ar.php';
        } else {
            $sections = require ERSAAL_PLUGIN_DIR . 'admin/help/en.php';
        }

        require ERSAAL_PLUGIN_DIR . 'admin/views/help.php';
    }
}
