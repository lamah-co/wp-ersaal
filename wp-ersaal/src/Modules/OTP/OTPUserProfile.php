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
                'verified' => __('Verified', 'ersaal'),
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
        ?>
        <div class="ersaal-admin ersaal-profile-otp" data-user-id="<?php echo esc_attr((string) $user->ID); ?>">
            <h2><?php esc_html_e('Ersaal phone verification', 'ersaal'); ?></h2>
            <?php wp_nonce_field('ersaal_otp_profile_save_' . $user->ID, 'ersaal_otp_profile_save_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="ersaal_otp_phone"><?php esc_html_e('Mobile phone', 'ersaal'); ?></label></th>
                    <td>
                        <div class="ersaal-profile-phone-row">
                            <input type="tel" name="ersaal_otp_phone" id="ersaal_otp_phone" class="regular-text ersaal-input ersaal-ltr" value="<?php echo esc_attr($phone); ?>" autocomplete="tel" placeholder="+2189XXXXXXXX" />
                            <?php if ($verified): ?>
                                <span class="ersaal-badge ersaal-badge-success" id="ersaal-otp-profile-status"><?php esc_html_e('Verified', 'ersaal'); ?></span>
                            <?php else: ?>
                                <span class="ersaal-badge ersaal-badge-warning" id="ersaal-otp-profile-status"><?php esc_html_e('Not verified', 'ersaal'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="description"><?php esc_html_e('Changing this number removes its verified status and turns off login OTP until the new number is verified.', 'ersaal'); ?></p>
                        <?php if ($verified && $verifiedAt !== ''): ?>
                            <p class="description" id="ersaal-otp-profile-verified-at"><?php printf(esc_html__('Verified on %s.', 'ersaal'), esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($verifiedAt . ' UTC')))); ?></p>
                        <?php endif; ?>
                        <div id="ersaal-otp-profile-feedback" class="ersaal-alert" role="status" aria-live="polite" hidden></div>
                        <div class="ersaal-inline-actions ersaal-profile-verify-actions">
                            <button type="button" class="ersaal-btn ersaal-btn-secondary" id="ersaal-otp-profile-send" <?php disabled(!$otpAvailable); ?>><?php esc_html_e('Send verification code', 'ersaal'); ?></button>
                            <span class="spinner" id="ersaal-otp-profile-spinner" aria-hidden="true"></span>
                        </div>
                        <div class="ersaal-profile-code" id="ersaal-otp-profile-code-wrap" hidden>
                            <label class="ersaal-label" for="ersaal-otp-profile-code"><?php esc_html_e('Verification code', 'ersaal'); ?></label>
                            <div class="ersaal-inline-actions">
                                <input type="text" id="ersaal-otp-profile-code" class="ersaal-input ersaal-ltr ersaal-otp-code-input" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="one-time-code" />
                                <button type="button" class="ersaal-btn ersaal-btn-primary" id="ersaal-otp-profile-verify"><?php esc_html_e('Verify phone', 'ersaal'); ?></button>
                            </div>
                        </div>
                        <?php if (!$otpAvailable): ?>
                            <p class="description"><?php esc_html_e('An administrator must enable OTP in Ersaal settings before this phone can be verified.', 'ersaal'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Login OTP', 'ersaal'); ?></th>
                    <td>
                        <label for="ersaal_otp_login_2fa">
                            <input type="hidden" name="ersaal_otp_login_2fa" value="0" />
                            <input type="checkbox" name="ersaal_otp_login_2fa" id="ersaal_otp_login_2fa" value="1" <?php checked($loginEnabled); ?> <?php disabled(!$verified); ?> />
                            <?php esc_html_e('Require a verification code after my WordPress password', 'ersaal'); ?>
                        </label>
                        <p class="description" id="ersaal-otp-login-description"><?php esc_html_e('This opt-in applies only to this user and requires a verified phone. The administrator can disable login OTP globally.', 'ersaal'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
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
        update_user_meta($userId, self::META_LOGIN_ENABLED, $verified && $loginEnabled ? 1 : 0);
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

    private function safeHttpStatus(OTPResult $result): int
    {
        $status = $result->getHttpStatus();
        return $status >= 400 && $status <= 599 ? $status : 400;
    }
}
