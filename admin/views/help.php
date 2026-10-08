<?php
declare(strict_types=1);
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!defined('ABSPATH')) {
    exit;
}

$pageTitle = $is_arabic ? 'المساعدة ودليل الاستخدام' : 'Help & User Guide';
$pageDescription = $is_arabic ? 'ابحث عن خطوات الإعداد والإرسال وحل المشكلات.' : 'Find setup, sending, and troubleshooting guidance.';
$searchPlaceholder = $is_arabic ? 'ابحث في الدليل...' : 'Search the guide...';
$noResultsText = $is_arabic ? 'لا توجد نتائج مطابقة. جرّب عبارة بحث أخرى.' : 'No matching guidance. Try another search term.';
$languageLabel = $is_arabic ? 'لغة الدليل' : 'Guide language';
$topicsLabel = $is_arabic ? 'موضوعات الدليل' : 'Guide topics';
$shortcutsLabel = $is_arabic ? 'اختصارات مفيدة' : 'Useful shortcuts';
$browseTitle = $is_arabic ? 'كل ما تحتاجه في مكان واحد' : 'Everything you need in one place';
$browseDescription = $is_arabic ? 'افتح القسم الذي تحتاجه فقط، أو استخدم البحث للوصول إلى الإجابة بسرعة.' : 'Open only the section you need, or use search to reach an answer quickly.';
$settingsLabel = $is_arabic ? 'إعداد الاتصال' : 'Connection settings';
$settingsDescription = $is_arabic ? 'ربط الموقع والتحقق من بيانات API.' : 'Connect the site and verify API credentials.';
$sendLabel = $is_arabic ? 'إرسال رسالة' : 'Send a message';
$sendDescription = $is_arabic ? 'إرسال رسالة نصية يدوية.' : 'Send a manual SMS message.';
$logsLabel = $is_arabic ? 'مراجعة السجلات' : 'Review logs';
$logsDescription = $is_arabic ? 'تتبع الحالات ومعالجة الأخطاء.' : 'Track statuses and troubleshoot errors.';
$otpLabel = $is_arabic ? 'اختبار رمز التحقق' : 'Test OTP';
$otpDescription = $is_arabic ? 'تحقق من الإرسال والتأكيد خطوة بخطوة.' : 'Validate sending and verification step by step.';
?>
<div class="wrap ersaal-admin ersaal-page ersaal-page-wide ersaal-help-page" dir="<?php echo $is_arabic ? 'rtl' : 'ltr'; ?>" lang="<?php echo $is_arabic ? 'ar' : 'en'; ?>">
    <header class="ersaal-page-header">
        <div class="ersaal-page-header-copy">
            <h1 class="ersaal-page-title"><?php echo esc_html($pageTitle); ?></h1>
            <p class="ersaal-page-description"><?php echo esc_html($pageDescription); ?></p>
        </div>
    </header>

    <section class="ersaal-help-toolbar" aria-label="<?php echo esc_attr($is_arabic ? 'أدوات الدليل' : 'Guide tools'); ?>">
        <div class="ersaal-help-search ersaal-field">
            <label class="ersaal-label" for="ersaal-help-search"><?php echo esc_html($is_arabic ? 'ما الذي تبحث عنه؟' : 'What do you need help with?'); ?></label>
            <input type="search" id="ersaal-help-search" class="ersaal-input" placeholder="<?php echo esc_attr($searchPlaceholder); ?>" autocomplete="off" />
            <span id="ersaal-help-search-status" class="ersaal-help-results" aria-live="polite"></span>
        </div>

        <div class="ersaal-help-language-wrap">
            <form method="get" action="admin.php" class="ersaal-help-language" id="ersaal-help-language-form">
                <input type="hidden" name="page" value="ersaal-help" />
                <label for="ersaal-help-language"><?php echo esc_html($languageLabel); ?></label>
                <select id="ersaal-help-language" name="lang" class="ersaal-select">
                    <option value="auto" <?php selected($lang_override === '' || $lang_override === 'auto'); ?>><?php echo esc_html($is_arabic ? 'تلقائي' : 'Auto'); ?> (<?php echo esc_html(get_user_locale()); ?>)</option>
                    <option value="ar" <?php selected($lang_override, 'ar'); ?>>العربية</option>
                    <option value="en" <?php selected($lang_override, 'en'); ?>><?php echo esc_html($is_arabic ? 'الإنجليزية' : 'English'); ?></option>
                </select>
            </form>
        </div>
    </section>

    <nav id="ersaal-help-toc" class="ersaal-help-nav" aria-label="<?php echo esc_attr($topicsLabel); ?>">
        <?php foreach ($sections as $index => $section): ?>
            <a href="#<?php echo esc_attr($section['id']); ?>" <?php echo $index === 0 ? 'class="active" aria-current="location"' : ''; ?>><?php echo esc_html($section['title']); ?></a>
        <?php endforeach; ?>
    </nav>

    <main class="ersaal-help-content" id="ersaal-help-content">
        <section class="ersaal-help-overview" aria-labelledby="ersaal-help-overview-title">
            <div>
                <h2 id="ersaal-help-overview-title"><?php echo esc_html($browseTitle); ?></h2>
                <p><?php echo esc_html($browseDescription); ?></p>
            </div>

            <div class="ersaal-help-shortcuts" aria-label="<?php echo esc_attr($shortcutsLabel); ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings')); ?>" class="ersaal-help-shortcut">
                    <?php echo ersaal_admin_icon('settings'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><strong><?php echo esc_html($settingsLabel); ?></strong><small><?php echo esc_html($settingsDescription); ?></small></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-send-message')); ?>" class="ersaal-help-shortcut">
                    <?php echo ersaal_admin_icon('send'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><strong><?php echo esc_html($sendLabel); ?></strong><small><?php echo esc_html($sendDescription); ?></small></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-logs')); ?>" class="ersaal-help-shortcut">
                    <?php echo ersaal_admin_icon('logs'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><strong><?php echo esc_html($logsLabel); ?></strong><small><?php echo esc_html($logsDescription); ?></small></span>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-otp-test')); ?>" class="ersaal-help-shortcut">
                    <?php echo ersaal_admin_icon('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span><strong><?php echo esc_html($otpLabel); ?></strong><small><?php echo esc_html($otpDescription); ?></small></span>
                </a>
            </div>
        </section>

        <div id="ersaal-help-no-results" class="ersaal-alert ersaal-alert-warning" role="status" hidden>
            <p><?php echo esc_html($noResultsText); ?></p>
        </div>

        <div class="ersaal-help-sections">
            <?php foreach ($sections as $index => $section): ?>
                <details id="<?php echo esc_attr($section['id']); ?>" class="ersaal-help-section" <?php echo $index === 0 ? 'open' : ''; ?>>
                    <summary>
                        <span><?php echo esc_html($section['title']); ?></span>
                        <?php echo ersaal_admin_icon('chevron', 'ersaal-help-chevron'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </summary>
                    <div class="ersaal-help-section-body">
                        <?php echo wp_kses_post($section['content']); ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </main>
</div>
