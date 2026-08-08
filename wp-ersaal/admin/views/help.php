<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-normal">
    <div class="ersaal-page-header">
        <div>
            <h1 class="ersaal-page-title">
                <?php echo esc_html($is_arabic ? 'المساعدة ودليل الاستخدام' : 'Help & User Guide'); ?>
            </h1>
        </div>
        <div>
            <form method="get" action="admin.php" style="margin: 0;">
                <input type="hidden" name="page" value="ersaal-help">
                <select name="lang" class="ersaal-select" onchange="this.form.submit()" style="padding-block: 4px; height: auto;">
                    <option value="auto" <?php selected($lang_override, ''); ?>>Auto (<?php echo esc_html(get_user_locale()); ?>)</option>
                    <option value="ar" <?php selected($lang_override, 'ar'); ?>>العربية</option>
                    <option value="en" <?php selected($lang_override, 'en'); ?>>English</option>
                </select>
            </form>
        </div>
    </div>

    <div class="ersaal-help-wrap" style="display: flex; gap: var(--ersaal-space-xl); align-items: flex-start; margin-top: var(--ersaal-space-lg);">
        <aside class="ersaal-help-sidebar" style="width: 250px; flex-shrink: 0; position: sticky; top: 40px; background: var(--ersaal-bg-surface); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-md); padding: var(--ersaal-space-md) 0; box-shadow: var(--ersaal-shadow-sm);">
            <div style="padding: 0 var(--ersaal-space-md) var(--ersaal-space-sm); border-bottom: 1px solid var(--ersaal-border-color); margin-bottom: var(--ersaal-space-sm);">
                <input type="text" id="ersaal-help-search" class="ersaal-input" placeholder="<?php echo esc_attr($is_arabic ? 'ابحث في الدليل...' : 'Search help...'); ?>" style="width: 100%;">
            </div>
            <nav id="ersaal-help-toc" style="display: flex; flex-direction: column;">
                <?php foreach ($sections as $index => $section): ?>
                    <a href="#<?php echo esc_attr($section['id']); ?>" <?php if ($index === 0) echo 'class="active"'; ?> style="padding: var(--ersaal-space-sm) var(--ersaal-space-md); text-decoration: none; color: var(--ersaal-text-primary); font-weight: var(--ersaal-fw-medium); border-inline-start: 3px solid transparent; transition: background-color var(--ersaal-transition-fast);">
                        <?php echo esc_html($section['title']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="ersaal-help-content" style="flex-grow: 1; background: var(--ersaal-bg-surface); border: 1px solid var(--ersaal-border-color); border-radius: var(--ersaal-radius-md); padding: var(--ersaal-space-xl); min-width: 0; box-shadow: var(--ersaal-shadow-sm);">
            <div id="ersaal-help-no-results" class="ersaal-alert ersaal-alert-warning" style="display:none; margin-bottom: var(--ersaal-space-lg);">
                <p style="margin: 0;"><?php echo esc_html($is_arabic ? 'لا توجد نتائج مطابقة لبحثك.' : 'No results found.'); ?></p>
            </div>
            
            <?php foreach ($sections as $section): ?>
                <section id="<?php echo esc_attr($section['id']); ?>" class="ersaal-help-section" style="margin-bottom: var(--ersaal-space-xxl);">
                    <h2 style="font-size: var(--ersaal-text-xl); border-bottom: 1px solid var(--ersaal-border-color); padding-bottom: var(--ersaal-space-sm); margin-bottom: var(--ersaal-space-lg); color: var(--ersaal-text-primary);"><?php echo esc_html($section['title']); ?></h2>
                    <div class="ersaal-help-section-body" style="color: var(--ersaal-text-secondary); line-height: 1.6;">
                        <?php echo wp_kses_post($section['content']); ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<style>
/* CSS specific overrides for the active link and accordions to use tokens if possible */
.ersaal-help-sidebar a:hover, .ersaal-help-sidebar a.active {
    background: var(--ersaal-bg-surface-2) !important;
    color: var(--ersaal-color-primary) !important;
    border-inline-start-color: var(--ersaal-color-primary) !important;
}

@media screen and (max-width: 782px) {
    .ersaal-help-wrap { flex-direction: column !important; }
    .ersaal-help-sidebar { width: 100% !important; position: static !important; max-height: 200px; overflow-y: auto; }
}

/* Accordion overrides using tokens */
.ersaal-accordion-btn {
    background-color: var(--ersaal-bg-surface-2) !important;
    color: var(--ersaal-text-primary) !important;
    border: 1px solid var(--ersaal-border-color) !important;
    padding: var(--ersaal-space-md) var(--ersaal-space-lg) !important;
    font-weight: var(--ersaal-fw-semibold) !important;
    border-radius: var(--ersaal-radius-sm) !important;
}
.ersaal-accordion-btn.active, .ersaal-accordion-btn:hover {
    background-color: var(--ersaal-bg-body) !important;
}
.ersaal-accordion-panel.show {
    border-color: var(--ersaal-border-color) !important;
    margin-bottom: var(--ersaal-space-md) !important;
}
.ersaal-help-quick-start {
    background: var(--ersaal-color-info-bg) !important;
    border-inline-start: 4px solid var(--ersaal-color-info) !important;
    border-radius: var(--ersaal-radius-sm) !important;
}
</style>
