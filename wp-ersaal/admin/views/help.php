<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-help-page">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1 class="wp-heading-inline">
            <?php echo esc_html($is_arabic ? 'المساعدة ودليل الاستخدام' : 'Help & User Guide'); ?>
        </h1>
        <form method="get" action="admin.php">
            <input type="hidden" name="page" value="ersaal-help">
            <select name="lang" onchange="this.form.submit()">
                <option value="auto" <?php selected($lang_override, ''); ?>>Auto (<?php echo esc_html(get_user_locale()); ?>)</option>
                <option value="ar" <?php selected($lang_override, 'ar'); ?>>العربية</option>
                <option value="en" <?php selected($lang_override, 'en'); ?>>English</option>
            </select>
        </form>
    </div>

    <div class="ersaal-help-wrap">
        <aside class="ersaal-help-sidebar">
            <div style="padding: 0 15px 10px; border-bottom: 1px solid #ccd0d4; margin-bottom: 10px;">
                <input type="text" id="ersaal-help-search" placeholder="<?php echo esc_attr($is_arabic ? 'ابحث في الدليل...' : 'Search help...'); ?>" style="width: 100%;">
            </div>
            <nav id="ersaal-help-toc">
                <?php foreach ($sections as $index => $section): ?>
                    <a href="#<?php echo esc_attr($section['id']); ?>" <?php if ($index === 0) echo 'class="active"'; ?>>
                        <?php echo esc_html($section['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="ersaal-help-content">
            <div id="ersaal-help-no-results" style="display:none;">
                <p><?php echo esc_html($is_arabic ? 'لا توجد نتائج مطابقة لبحثك.' : 'No results found.'); ?></p>
            </div>
            
            <?php foreach ($sections as $section): ?>
                <section id="<?php echo esc_attr($section['id']); ?>" class="ersaal-help-section">
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <div class="ersaal-help-section-body">
                        <?php echo wp_kses_post($section['content']); ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    </div>
</div>
