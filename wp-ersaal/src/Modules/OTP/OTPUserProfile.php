<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\Core\Options;

final class OTPUserProfile
{
    public const META_PHONE = 'ersaal_otp_phone';
    public const META_VERIFIED = 'ersaal_otp_phone_verified';
    public const META_VERIFIED_AT = 'ersaal_otp_phone_verified_at';
    public const META_LOGIN_ENABLED = 'ersaal_otp_login_2fa';

    private Options $options;
    private OTPService $service;
    private OTPValidator $validator;

    public function __construct(Options $options, OTPService $service)
    {
        $this->options = $options;
        $this->service = $service;
        $this->validator = new OTPValidator();
    }

    public function register(): void
    {
        add_action('show_user_profile', [$this, 'render']);
        add_action('edit_user_profile', [$this, 'render']);
        add_action('user_profile_update_errors', [$this, 'validateProfile'], 10, 3);
        add_action('personal_options_update', [$this, 'save']);
        add_action('edit_user_profile_update', [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_ersaal_otp_profile_use_suggested', [$this, 'ajaxUseSuggestedPhone']);
        add_action('wp_ajax_ersaal_otp_profile_send', [$this, 'ajaxSend']);
        add_action('wp_ajax_ersaal_otp_profile_verify', [$this, 'ajaxVerify']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['profile.php', 'user-edit.php'], true)) {
            return;
        }
        wp_enqueue_style('ersaal-tokens', ERSAAL_PLUGIN_URL . 'admin/assets/css/ersaal-tokens.css', [], ERSAAL_VERSION);
        wp_enqueue_style('ersaal-admin', ERSAAL_PLUGIN_URL . 'admin/assets/css/admin.css', ['ersaal-tokens'], ERSAAL_VERSION);
        wp_enqueue_script('ersaal-otp-profile', ERSAAL_PLUGIN_URL . 'admin/assets/js/otp-profile.js', [], ERSAAL_VERSION, true);
        wp_localize_script('ersaal-otp-profile', 'ersaalOtpProfile', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ersaal_otp_profile'),
            'i18n' => [
                'send' => __('Send verification code', 'ersaal'),
                'sending' => __('Sending...', 'ersaal'),
                'verify' => __('Verify phone', 'ersaal'),
                'verifying' => __('Verifying...', 'ersaal'),
                'resend' => __('Resend code', 'ersaal'),
                'useSuggested' => __('Use this number', 'ersaal'),
                'loadingSuggested' => __('Loading number...', 'ersaal'),
                'verified' => __('Verified', 'ersaal'),
                'notVerified' => __('Not verified', 'ersaal'),
                'phoneChanged' => __('Verify the new phone before enabling login 2FA.', 'ersaal'),
                'loginReady' => __('The verified phone can now be used for login 2FA. Enable it, then save the profile.', 'ersaal'),
                'unexpected' => __('The phone verification request could not be completed.', 'ersaal'),
            ],
        ]);
    }

    public function render(\WP_User $user): void
    {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        $phone = (string) get_user_meta($user->ID, self::META_PHONE, true);
        $verified = (bool) get_user_meta($user->ID, self::META_VERIFIED, true);
        $verifiedAt = (string) get_user_meta($user->ID, self::META_VERIFIED_AT, true);
        $loginEnabled = (bool) get_user_meta($user->ID, self::META_LOGIN_ENABLED, true);
        $otpAvailable = $this->service->isEnabled();
        $loginGloballyAllowed = (bool) $this->options->get('otp_login_enabled', false);
        $emergencyDisabled = defined('ERSAAL_DISABLE_LOGIN_OTP') && ERSAAL_DISABLE_LOGIN_OTP;
        $canEnableLogin = $this->canEnableLoginTwoFactor($user->ID, $phone, $verified);
        $suggested = $this->getSuggestedPhone($user);
        $accountName = $user->display_name !== '' ? $user->display_name : $user->user_login;
        ?>
        <section class="ersaal-admin ersaal-profile-otp" data-user-id="<?php echo esc_attr((string) $user->ID); ?>" data-login-configured="<?php echo $otpAvailable && $loginGloballyAllowed && !$emergencyDisabled ? '1' : '0'; ?>" aria-labelledby="ersaal-security-title">
            <?php wp_nonce_field('ersaal_otp_profile_save_' . $user->ID, 'ersaal_otp_profile_save_nonce'); ?>
            <header class="ersaal-profile-security-header">
                <div>
                    <h2 id="ersaal-security-title"><?php esc_html_e('Ersaal Security', 'ersaal'); ?></h2>
                    <p><?php esc_html_e('Confirm the account phone, verify it with Ersaal, then choose whether to protect WordPress login.', 'ersaal'); ?></p>
                </div>
                <?php if ($verified): ?>
                    <span class="ersaal-badge ersaal-badge-success"><?php esc_html_e('Phone verified', 'ersaal'); ?></span>
                <?php else: ?>
                    <span class="ersaal-badge ersaal-badge-warning"><?php esc_html_e('Setup incomplete', 'ersaal'); ?></span>
                <?php endif; ?>
            </header>

            <?php if (!$otpAvailable): ?>
                <div class="ersaal-alert ersaal-alert-warning" role="status">
                    <p class="ersaal-alert-title"><?php esc_html_e('OTP service is currently disabled.', 'ersaal'); ?></p>
                    <p>
                        <?php esc_html_e('The phone can be entered now, but verification requires OTP to be enabled.', 'ersaal'); ?>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ersaal-settings&tab=otp')); ?>"><?php esc_html_e('Open Ersaal OTP settings', 'ersaal'); ?></a>
                        <?php else: ?>
                            <?php esc_html_e('Ask an administrator to enable OTP in Ersaal settings.', 'ersaal'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="ersaal-profile-security-grid">
                <article class="ersaal-card ersaal-profile-security-card">
                    <header class="ersaal-profile-card-header">
                        <div>
                            <span class="ersaal-profile-step" aria-hidden="true">1</span>
                            <h3><?php esc_html_e('Account identity', 'ersaal'); ?></h3>
                        </div>
                        <p><?php esc_html_e('This identifies the WordPress account only. Ersaal sends OTP by SMS, not email.', 'ersaal'); ?></p>
                    </header>
                    <dl class="ersaal-profile-account-list">
                        <div><dt><?php esc_html_e('Account', 'ersaal'); ?></dt><dd><?php echo esc_html($accountName); ?> <span class="ersaal-table-meta ersaal-ltr">@<?php echo esc_html($user->user_login); ?></span></dd></div>
                        <div><dt><?php esc_html_e('Email', 'ersaal'); ?></dt><dd class="ersaal-ltr"><?php echo esc_html($user->user_email); ?></dd></div>
                    </dl>
                </article>

                <article class="ersaal-card ersaal-profile-security-card ersaal-profile-phone-card">
                    <header class="ersaal-profile-card-header">
                        <div>
                            <span class="ersaal-profile-step" aria-hidden="true">2</span>
                            <h3><?php esc_html_e('OTP phone', 'ersaal'); ?></h3>
                        </div>
                        <span class="ersaal-badge <?php echo $verified ? 'ersaal-badge-success' : 'ersaal-badge-warning'; ?>" id="ersaal-otp-profile-status"><?php echo esc_html($verified ? __('Verified', 'ersaal') : __('Not verified', 'ersaal')); ?></span>
                    </header>

                    <?php if ($suggested): ?>
                        <div class="ersaal-profile-suggestion">
                            <div>
                                <span class="ersaal-label"><?php esc_html_e('Suggested phone', 'ersaal'); ?></span>
                                <strong class="ersaal-ltr"><?php echo esc_html($suggested['masked']); ?></strong>
                                <p><?php esc_html_e('We found this number in the WooCommerce billing details for this account. It is not verified yet.', 'ersaal'); ?></p>
                            </div>
                            <button type="button" class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm" id="ersaal-otp-use-suggested"><?php esc_html_e('Use this number', 'ersaal'); ?></button>
                        </div>
                    <?php endif; ?>

                    <div class="ersaal-field">
                        <label class="ersaal-label" for="ersaal_otp_phone"><?php esc_html_e('OTP phone', 'ersaal'); ?></label>
                        <input type="tel" name="ersaal_otp_phone" id="ersaal_otp_phone" class="ersaal-input ersaal-ltr" value="<?php echo esc_attr($phone); ?>" autocomplete="tel" inputmode="tel" placeholder="0912345678" />
                        <p class="ersaal-field-help">
                            <?php esc_html_e('Libyana or Almadar: 091 / 092 / 093 / 094', 'ersaal'); ?><br>
                            <?php esc_html_e('Changing it clears verification and turns off login 2FA until the new number is verified.', 'ersaal'); ?>
                        </p>
                    </div>

                    <?php if ($verified && $verifiedAt !== ''): ?>
                        <p class="ersaal-profile-verified-at" id="ersaal-otp-profile-verified-at"><?php printf(esc_html__('Verified on %s.', 'ersaal'), esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($verifiedAt . ' UTC')))); ?></p>
                    <?php endif; ?>

                    <div id="ersaal-otp-profile-feedback" class="ersaal-alert" role="status" aria-live="polite" hidden></div>
                    <div class="ersaal-inline-actions ersaal-profile-verify-actions">
                        <button type="button" class="ersaal-btn ersaal-btn-primary" id="ersaal-otp-profile-send" <?php disabled(!$otpAvailable); ?>><?php esc_html_e('Send verification code', 'ersaal'); ?></button>
                        <button type="button" class="ersaal-btn ersaal-btn-secondary" id="ersaal-otp-profile-resend" hidden <?php disabled(!$otpAvailable); ?>><?php esc_html_e('Resend code', 'ersaal'); ?></button>
                        <span class="spinner" id="ersaal-otp-profile-spinner" aria-hidden="true"></span>
                    </div>
                    <div class="ersaal-profile-code" id="ersaal-otp-profile-code-wrap" hidden>
                        <label class="ersaal-label" for="ersaal-otp-profile-code"><?php esc_html_e('Verification code', 'ersaal'); ?></label>
                        <div class="ersaal-inline-actions">
                            <input type="text" id="ersaal-otp-profile-code" class="ersaal-input ersaal-ltr ersaal-otp-code-input" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="one-time-code" aria-describedby="ersaal-otp-profile-feedback" />
                            <button type="button" class="ersaal-btn ersaal-btn-primary" id="ersaal-otp-profile-verify"><?php esc_html_e('Verify phone', 'ersaal'); ?></button>
                        </div>
                    </div>
                </article>

                <article class="ersaal-card ersaal-profile-security-card ersaal-profile-login-card">
                    <header class="ersaal-profile-card-header">
                        <div>
                            <span class="ersaal-profile-step" aria-hidden="true">3</span>
                            <h3><?php esc_html_e('Login two-factor authentication', 'ersaal'); ?></h3>
                        </div>
                    </header>

                    <?php if ($emergencyDisabled): ?>
                        <div class="ersaal-alert ersaal-alert-warning" role="status"><p><?php esc_html_e('Login OTP is currently disabled by wp-config.php. Phone verification remains available.', 'ersaal'); ?></p></div>
                    <?php elseif (!$loginGloballyAllowed): ?>
                        <div class="ersaal-alert ersaal-alert-info" role="status"><p><?php esc_html_e('Login 2FA is disabled globally by an administrator. Phone verification remains available.', 'ersaal'); ?></p></div>
                    <?php endif; ?>

                    <label class="ersaal-switch-row" for="ersaal_otp_login_2fa">
                        <span class="ersaal-switch-copy">
                            <span class="ersaal-switch-title"><?php esc_html_e('Enable login 2FA', 'ersaal'); ?></span>
                            <span class="ersaal-switch-description" id="ersaal-otp-login-description">
                                <?php if (!$phone || !$verified): ?>
                                    <?php esc_html_e('Verify your phone number before enabling login 2FA.', 'ersaal'); ?>
                                <?php elseif (!$canEnableLogin): ?>
                                    <?php esc_html_e('Login 2FA cannot be enabled until the global login OTP setting is available.', 'ersaal'); ?>
                                <?php else: ?>
                                    <?php esc_html_e('Require a verification code after the WordPress password for this account.', 'ersaal'); ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="ersaal-switch-control">
                            <input type="hidden" name="ersaal_otp_login_2fa" value="0" />
                            <input type="checkbox" name="ersaal_otp_login_2fa" id="ersaal_otp_login_2fa" value="1" <?php checked($loginEnabled && $canEnableLogin); ?> <?php disabled(!$canEnableLogin); ?> aria-describedby="ersaal-otp-login-description" />
                            <span class="ersaal-switch-track" aria-hidden="true"></span>
                        </span>
                    </label>
                </article>
            </div>
        </section>
        <?php
    }

    /**
     * WooCommerce stores the real account billing phone in billing_phone user
     * meta. WordPress Core has no standard user phone field, so no speculative
     * meta keys are consulted.
     */
    public function getSuggestedPhone(\WP_User $user): ?array
    {
        if ((string) get_user_meta($user->ID, self::META_PHONE, true) !== '') {
            return null;
        }
        $candidate = (string) get_user_meta($user->ID, 'billing_phone', true);
        if ($candidate === '') {
            return null;
        }
        try {
            $normalized = $this->validator->normalizePhone($candidate);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
        return ['phone' => $normalized, 'masked' => $this->validator->maskPhone($normalized)];
    }

    public function validateProfile(\WP_Error $errors, bool $update, \stdClass $user): void
    {
        if (!isset($_POST['ersaal_otp_profile_save_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ersaal_otp_profile_save_nonce'])), 'ersaal_otp_profile_save_' . $user->ID)) {
            return;
        }
        $phone = isset($_POST['ersaal_otp_phone']) ? sanitize_text_field(wp_unslash($_POST['ersaal_otp_phone'])) : '';
        if ($phone === '') {
            return;
        }
        try {
            $this->validator->normalizePhone($phone);
        } catch (\InvalidArgumentException $e) {
            $errors->add('ersaal_otp_phone', $e->getMessage());
        }
    }

    public function save(int $userId): void
    {
        if (!current_user_can('edit_user', $userId)) {
            return;
        }
        if (!isset($_POST['ersaal_otp_profile_save_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ersaal_otp_profile_save_nonce'])), 'ersaal_otp_profile_save_' . $userId)) {
            return;
        }

        $submitted = isset($_POST['ersaal_otp_phone']) ? sanitize_text_field(wp_unslash($_POST['ersaal_otp_phone'])) : '';
        $existing = (string) get_user_meta($userId, self::META_PHONE, true);
        if ($submitted === '') {
            delete_user_meta($userId, self::META_PHONE);
            $this->clearVerification($userId);
            return;
        }
        try {
            $phone = $this->validator->normalizePhone($submitted);
        } catch (\InvalidArgumentException $e) {
            return;
        }
        update_user_meta($userId, self::META_PHONE, $phone);
        if ($existing !== $phone) {
            $this->clearVerification($userId);
            return;
        }

        $verified = (bool) get_user_meta($userId, self::META_VERIFIED, true);
        $loginEnabled = isset($_POST['ersaal_otp_login_2fa']) && (string) wp_unslash($_POST['ersaal_otp_login_2fa']) === '1';
        update_user_meta($userId, self::META_LOGIN_ENABLED, $loginEnabled && $this->canEnableLoginTwoFactor($userId, $phone, $verified) ? 1 : 0);
    }

    public function ajaxUseSuggestedPhone(): void
    {
        $userId = $this->authorizeAjax();
        $user = get_userdata($userId);
        $suggested = $user instanceof \WP_User ? $this->getSuggestedPhone($user) : null;
        if (!$suggested) {
            wp_send_json_error(['message' => __('No suggested phone is available for this account.', 'ersaal')], 404);
        }
        wp_send_json_success([
            'phone' => $suggested['phone'],
            'masked' => $suggested['masked'],
            'message' => __('The suggested phone was added. Send a code to verify it before enabling login 2FA.', 'ersaal'),
        ]);
    }

    public function ajaxSend(): void
    {
        $userId = $this->authorizeAjax();
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $key = $this->transientKey($userId);
        $state = get_transient($key);
        $now = time();
        if (is_array($state) && !empty($state['expires_at']) && (int) $state['expires_at'] > $now) {
            wp_send_json_error([
                'message' => __('A code is already active for this phone. Enter it before requesting another.', 'ersaal'),
                'retry_after' => (int) $state['expires_at'] - $now,
            ], 429);
        }

        $result = $this->service->initiate($phone, 'profile_verification', ['user_id' => $userId]);
        if (!$result->isSuccess()) {
            wp_send_json_error(['message' => $result->getErrorMessage(), 'status' => $result->getStatus()], $this->safeHttpStatus($result));
        }
        $normalized = $this->validator->normalizePhone($phone);
        $expiresIn = (int) ($result->getExpiresIn() ?: $this->service->getConfiguredLifetimeSeconds());
        set_transient($key, [
            'reference' => $result->getReference(),
            'phone' => $normalized,
            'expires_at' => $now + $expiresIn,
        ], $expiresIn + (5 * MINUTE_IN_SECONDS));
        wp_send_json_success([
            'message' => sprintf(__('A verification code was sent to %s.', 'ersaal'), $this->validator->maskPhone($normalized)),
            'expires_in' => $expiresIn,
        ]);
    }

    public function ajaxVerify(): void
    {
        $userId = $this->authorizeAjax();
        $key = $this->transientKey($userId);
        $state = get_transient($key);
        if (!is_array($state) || empty($state['reference']) || empty($state['phone'])) {
            wp_send_json_error(['message' => __('This phone verification has expired. Send a new code.', 'ersaal')], 410);
        }
        $code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
        $result = $this->service->verify(
            (string) $state['reference'],
            $code,
            'profile_verification',
            ['user_id' => $userId, 'phone' => (string) $state['phone']]
        );
        unset($code);
        if (!$result->isSuccess()) {
            wp_send_json_error(['message' => $result->getErrorMessage(), 'status' => $result->getStatus()], $this->safeHttpStatus($result));
        }

        $oldPhone = (string) get_user_meta($userId, self::META_PHONE, true);
        $phoneChanged = $oldPhone !== (string) $state['phone'];
        update_user_meta($userId, self::META_PHONE, (string) $state['phone']);
        update_user_meta($userId, self::META_VERIFIED, 1);
        update_user_meta($userId, self::META_VERIFIED_AT, current_time('mysql', true));
        if ($phoneChanged) {
            update_user_meta($userId, self::META_LOGIN_ENABLED, 0);
        }
        delete_transient($key);
        wp_send_json_success([
            'message' => __('Phone verified successfully. Save the profile to keep any login OTP preference.', 'ersaal'),
            'phone' => (string) $state['phone'],
            'login_reset' => $phoneChanged,
            'can_enable_login' => $this->canEnableLoginTwoFactor($userId),
        ]);
    }

    private function authorizeAjax(): int
    {
        check_ajax_referer('ersaal_otp_profile', 'nonce');
        $userId = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if (!$userId || !current_user_can('edit_user', $userId)) {
            wp_send_json_error(['message' => __('You are not allowed to verify this phone.', 'ersaal')], 403);
        }
        return $userId;
    }

    private function transientKey(int $userId): string
    {
        return 'ersaal_otp_enroll_' . $userId . '_' . get_current_user_id();
    }

    private function clearVerification(int $userId): void
    {
        delete_user_meta($userId, self::META_VERIFIED);
        delete_user_meta($userId, self::META_VERIFIED_AT);
        update_user_meta($userId, self::META_LOGIN_ENABLED, 0);
    }

    private function canEnableLoginTwoFactor(int $userId, ?string $phone = null, ?bool $verified = null): bool
    {
        $phone = $phone ?? (string) get_user_meta($userId, self::META_PHONE, true);
        $verified = $verified ?? (bool) get_user_meta($userId, self::META_VERIFIED, true);
        $emergencyDisabled = defined('ERSAAL_DISABLE_LOGIN_OTP') && ERSAAL_DISABLE_LOGIN_OTP;
        return $this->service->isEnabled()
            && (bool) $this->options->get('otp_login_enabled', false)
            && !$emergencyDisabled
            && $phone !== ''
            && $verified;
    }

    private function safeHttpStatus(OTPResult $result): int
    {
        $status = $result->getHttpStatus();
        return $status >= 400 && $status <= 599 ? $status : 400;
    }
}
