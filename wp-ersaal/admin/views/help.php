<?php
if (!defined('ABSPATH')) {
    exit;
}

$pageTitle = $is_arabic ? 'المساعدة ودليل الاستخدام' : 'Help & User Guide';
$pageDescription = $is_arabic ? 'ابحث عن خطوات الإعداد والإرسال وحل المشكلات.' : 'Find setup, sending, and troubleshooting guidance.';
$searchPlaceholder = $is_arabic ? 'ابحث في الدليل...' : 'Search the guide...';
$noResultsText = $is_arabic ? 'لا توجد نتائج مطابقة. جرّب عبارة بحث أخرى.' : 'No matching guidance. Try another search term.';
$languageLabel = $is_arabic ? 'لغة الدليل' : 'Guide language';
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-wide">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php echo esc_html($pageTitle); ?></h1>
            <p class="ersaal-page-description"><?php echo esc_html($pageDescription); ?></p>
        </div>
        <div class="ersaal-page-actions">
            <form method="get" action="admin.php" class="ersaal-help-language" id="ersaal-help-language-form">
                <input type="hidden" name="page" value="ersaal-help" />
                <label for="ersaal-help-language"><?php echo esc_html($languageLabel); ?></label>
                <select id="ersaal-help-language" name="lang" class="ersaal-select">
                    <option value="auto" <?php selected($lang_override === '' || $lang_override === 'auto'); ?>>Auto (<?php echo esc_html(get_user_locale()); ?>)</option>
                    <option value="ar" <?php selected($lang_override, 'ar'); ?>>العربية</option>
                    <option value="en" <?php selected($lang_override, 'en'); ?>>English</option>
                </select>
            </form>
        </div>
    </header>

    <div class="ersaal-help-wrap">
        <aside class="ersaal-help-sidebar" aria-label="<?php echo esc_attr($is_arabic ? 'فهرس الدليل' : 'Guide contents'); ?>">
            <div class="ersaal-help-search ersaal-field">
                <label class="ersaal-label" for="ersaal-help-search"><?php echo esc_html($is_arabic ? 'البحث' : 'Search'); ?></label>
                <input type="search" id="ersaal-help-search" class="ersaal-input" placeholder="<?php echo esc_attr($searchPlaceholder); ?>" autocomplete="off" />
                <span id="ersaal-help-search-status" class="screen-reader-text" aria-live="polite"></span>
            </div>
            <nav id="ersaal-help-toc" class="ersaal-help-nav">
                <?php foreach ($sections as $index => $section): ?>
                    <a href="#<?php echo esc_attr($section['id']); ?>" <?php echo $index === 0 ? 'class="active" aria-current="location"' : ''; ?>><?php echo esc_html($section['title']); ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="ersaal-help-content" id="ersaal-help-content">
            <div id="ersaal-help-no-results" class="ersaal-alert ersaal-alert-warning" role="status" hidden>
                <p><?php echo esc_html($noResultsText); ?></p>
            </div>

            <?php foreach ($sections as $section): ?>
                <section id="<?php echo esc_attr($section['id']); ?>" class="ersaal-help-section" tabindex="-1">
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <div class="ersaal-help-section-body">
                        <?php echo wp_kses_post($section['content']); ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>
    </div>
</div>
