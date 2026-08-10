<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\Core\Options;

final class OTPLoginTwoFactor
{
    private const COOKIE_NAME = 'ersaal_otp_challenge';
    private const MAX_FAILURES = 5;

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
        add_filter('authenticate', [$this, 'interceptAuthentication'], 99, 3);
        add_action('login_form_ersaal_otp', [$this, 'handleChallenge']);
        add_action('login_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * @param \WP_User|\WP_Error|null $user
     * @return \WP_User|\WP_Error|null
     */
    public function interceptAuthentication($user, $username, $password)
    {
        if (!$user instanceof \WP_User || !$this->isRequiredFor($user)) {
            return $user;
        }

        if (!$this->isInteractiveLogin()) {
            return new \WP_Error('ersaal_otp_required', __('Additional verification is required. Sign in through the WordPress login page.', 'ersaal'));
        }

        $existingToken = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME])) : '';
        $existing = $this->getChallenge($existingToken);
        if (is_array($existing) && (int) ($existing['user_id'] ?? 0) === $user->ID && (int) ($existing['cleanup_at'] ?? 0) > time()) {
            $this->redirectToChallenge();
        }

        $phone = (string) get_user_meta($user->ID, OTPUserProfile::META_PHONE, true);
        $result = $this->service->initiate($phone, 'wordpress_login', ['user_id' => $user->ID]);
        if (!$result->isSuccess()) {
            return new \WP_Error('ersaal_otp_unavailable', __('We could not start the verification step. Please try again later.', 'ersaal'));
        }

        $now = time();
        $expiresIn = (int) ($result->getExpiresIn() ?: 300);
        $token = $this->newToken();
        $state = [
            'user_id' => $user->ID,
            'reference' => $result->getReference(),
            'phone' => $phone,
            'remember' => !empty($_POST['rememberme']),
            'redirect_to' => $this->safeRedirect(isset($_POST['redirect_to']) ? (string) wp_unslash($_POST['redirect_to']) : ''),
            'expires_at' => $now + $expiresIn,
            'resend_after' => $now + $expiresIn + 5,
            'cleanup_at' => $now + $expiresIn + (5 * MINUTE_IN_SECONDS),
            'failures' => 0,
        ];
        set_transient($this->challengeKey($token), $state, $expiresIn + (5 * MINUTE_IN_SECONDS));
        $this->setChallengeCookie($token, (int) $state['cleanup_at']);
        $this->redirectToChallenge();

        return new \WP_Error('ersaal_otp_required', __('Additional verification is required.', 'ersaal'));
    }

    public function enqueueAssets(): void
    {
        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';
        if ($action !== 'ersaal_otp') {
            return;
        }
        wp_enqueue_style('ersaal-tokens', ERSAAL_PLUGIN_URL . 'admin/assets/css/ersaal-tokens.css', [], ERSAAL_VERSION);
        wp_enqueue_style('ersaal-otp-login', ERSAAL_PLUGIN_URL . 'admin/assets/css/otp-login.css', ['ersaal-tokens'], ERSAAL_VERSION);
    }

    public function handleChallenge(): void
    {
        // The opaque challenge token remains in an HttpOnly cookie and is never
        // placed in the URL, form, browser history, or referrer headers.
        $token = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_NAME])) : '';
        $error = new \WP_Error();
        $state = $this->getChallenge($token);

        if (!$this->isToken($token) || !is_array($state)) {
            $error->add('ersaal_otp_expired', __('This verification session is invalid or has expired. Sign in again.', 'ersaal'));
            $this->renderChallenge('', null, $error);
        }

        $user = get_user_by('id', (int) $state['user_id']);
        if (!$user instanceof \WP_User || !$this->isRequiredFor($user)) {
            $this->destroyChallenge($token);
            $error->add('ersaal_otp_unavailable', __('Verification is no longer available for this account. Sign in again.', 'ersaal'));
            $this->renderChallenge('', null, $error);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce = isset($_POST['ersaal_otp_login_nonce']) ? sanitize_text_field(wp_unslash($_POST['ersaal_otp_login_nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'ersaal_otp_login_' . $token)) {
                $error->add('ersaal_otp_nonce', __('The verification form expired. Refresh the page and try again.', 'ersaal'));
            } else {
                $operation = isset($_POST['otp_action']) ? sanitize_key(wp_unslash($_POST['otp_action'])) : 'verify';
                if ($operation === 'resend') {
                    $this->handleResend($token, $state, $error);
                } else {
                    $this->handleVerify($token, $state, $user, $error);
                }
                $state = $this->getChallenge($token);
            }
        }

        $this->renderChallenge($token, $state, $error);
    }

    private function handleVerify(string $token, array $state, \WP_User $user, \WP_Error $error): void
    {
        if ((int) $state['expires_at'] < time()) {
            $error->add('ersaal_otp_expired', __('The verification code expired. Request a new code.', 'ersaal'));
            return;
        }
        $code = isset($_POST['ersaal_otp_code']) ? sanitize_text_field(wp_unslash($_POST['ersaal_otp_code'])) : '';
        $result = $this->service->verify(
            (string) $state['reference'],
            $code,
            'wordpress_login',
            ['user_id' => $user->ID, 'phone' => (string) $state['phone']]
        );
        unset($code);
        if (!$result->isSuccess()) {
            $state['failures'] = (int) ($state['failures'] ?? 0) + 1;
            if ($state['failures'] >= self::MAX_FAILURES || $result->getStatus() === 'expired') {
                $this->destroyChallenge($token);
                $error->add('ersaal_otp_failed', __('This verification session ended. Sign in again to request a new code.', 'ersaal'));
                return;
            }
            $remaining = max(60, (int) $state['cleanup_at'] - time());
            set_transient($this->challengeKey($token), $state, $remaining);
            $error->add('ersaal_otp_failed', $result->getErrorMessage());
            return;
        }

        $remember = !empty($state['remember']);
        $redirect = $this->safeRedirect((string) ($state['redirect_to'] ?? ''));
        $this->destroyChallenge($token);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        do_action('wp_login', $user->user_login, $user);
        wp_safe_redirect($redirect);
        exit;
    }

    private function handleResend(string $token, array $state, \WP_Error $error): void
    {
        $now = time();
        if ((int) ($state['resend_after'] ?? 0) > $now) {
            $wait = (int) $state['resend_after'] - $now;
            $error->add('ersaal_otp_wait', sprintf(__('Wait %s seconds before requesting a new code.', 'ersaal'), $wait));
            return;
        }

        $result = $this->service->initiate((string) $state['phone'], 'wordpress_login', ['user_id' => (int) $state['user_id']]);
        if (!$result->isSuccess()) {
            $state['resend_after'] = $now + max(30, (int) ($result->getRetryAfter() ?: 0));
            $remaining = max(60, (int) $state['cleanup_at'] - $now);
            set_transient($this->challengeKey($token), $state, $remaining);
            $error->add('ersaal_otp_resend_failed', __('We could not send a new code. Please wait and try again.', 'ersaal'));
            return;
        }

        $expiresIn = (int) ($result->getExpiresIn() ?: 300);
        $state['reference'] = $result->getReference();
        $state['expires_at'] = $now + $expiresIn;
        $state['resend_after'] = $now + $expiresIn + 5;
        $state['cleanup_at'] = $now + $expiresIn + (5 * MINUTE_IN_SECONDS);
        $state['failures'] = 0;
        set_transient($this->challengeKey($token), $state, $expiresIn + (5 * MINUTE_IN_SECONDS));
        $this->setChallengeCookie($token, (int) $state['cleanup_at']);
        $error->add('ersaal_otp_sent', __('A new verification code was sent.', 'ersaal'), 'message');
    }

    private function renderChallenge(string $token, ?array $state, \WP_Error $error): void
    {
        login_header(__('Verify your sign-in', 'ersaal'), '', $error);
        if (!$state || $token === '') {
            echo '<p class="ersaal-otp-login-back"><a href="' . esc_url(wp_login_url()) . '">' . esc_html__('Back to login', 'ersaal') . '</a></p>';
            login_footer();
            exit;
        }

        $masked = $this->validator->maskPhone((string) $state['phone']);
        $canResend = time() >= (int) ($state['resend_after'] ?? 0);
        ?>
        <div class="ersaal-otp-login-intro">
            <h2><?php esc_html_e('Enter your verification code', 'ersaal'); ?></h2>
            <p><?php printf(esc_html__('We sent a one-time code to %s.', 'ersaal'), '<span class="ersaal-otp-login-phone">' . esc_html($masked) . '</span>'); ?></p>
        </div>
        <form name="ersaal-otp-login-form" id="ersaal-otp-login-form" action="<?php echo esc_url($this->challengeUrl()); ?>" method="post">
            <?php wp_nonce_field('ersaal_otp_login_' . $token, 'ersaal_otp_login_nonce'); ?>
            <p>
                <label for="ersaal_otp_code"><?php esc_html_e('Verification code', 'ersaal'); ?></label>
                <input type="text" name="ersaal_otp_code" id="ersaal_otp_code" class="input ersaal-otp-login-code" inputmode="numeric" pattern="[0-9]{4,6}" maxlength="6" autocomplete="one-time-code" autofocus required />
            </p>
            <p class="submit ersaal-otp-login-actions">
                <button type="submit" name="otp_action" value="verify" class="button button-primary button-large"><?php esc_html_e('Verify and sign in', 'ersaal'); ?></button>
                <button type="submit" name="otp_action" value="resend" class="button button-secondary button-large" formnovalidate <?php disabled(!$canResend); ?>><?php esc_html_e('Send a new code', 'ersaal'); ?></button>
            </p>
        </form>
        <p class="ersaal-otp-login-back"><a href="<?php echo esc_url(wp_login_url()); ?>"><?php esc_html_e('Back to login', 'ersaal'); ?></a></p>
        <?php
        login_footer();
        exit;
    }

    private function isRequiredFor(\WP_User $user): bool
    {
        if (!$this->service->isEnabled() || !(bool) $this->options->get('otp_login_enabled', false)) {
            return false;
        }
        if (defined('ERSAAL_OTP_DISABLE_LOGIN_2FA') && ERSAAL_OTP_DISABLE_LOGIN_2FA) {
            return false;
        }
        return (bool) get_user_meta($user->ID, OTPUserProfile::META_VERIFIED, true)
            && (bool) get_user_meta($user->ID, OTPUserProfile::META_LOGIN_ENABLED, true)
            && (string) get_user_meta($user->ID, OTPUserProfile::META_PHONE, true) !== '';
    }

    private function isInteractiveLogin(): bool
    {
        return isset($_SERVER['REQUEST_METHOD'])
            && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST'
            && isset($_POST['log'], $_POST['pwd'])
            && !(function_exists('wp_doing_ajax') && wp_doing_ajax())
            && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
            && !(defined('REST_REQUEST') && REST_REQUEST);
    }

    private function newToken(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            return hash('sha256', wp_generate_password(64, true, true) . microtime(true));
        }
    }

    private function isToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $token);
    }

    private function challengeKey(string $token): string
    {
        return 'ersaal_otp_login_' . hash('sha256', $token);
    }

    private function getChallenge(string $token): ?array
    {
        if (!$this->isToken($token)) {
            return null;
        }
        $state = get_transient($this->challengeKey($token));
        return is_array($state) ? $state : null;
    }

    private function destroyChallenge(string $token): void
    {
        if ($this->isToken($token)) {
            delete_transient($this->challengeKey($token));
        }
        $this->clearChallengeCookie();
    }

    private function setChallengeCookie(string $token, int $expires): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires' => $expires,
            'path' => defined('SITECOOKIEPATH') && SITECOOKIEPATH ? SITECOOKIEPATH : '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearChallengeCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - HOUR_IN_SECONDS,
            'path' => defined('SITECOOKIEPATH') && SITECOOKIEPATH ? SITECOOKIEPATH : '/',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function safeRedirect(string $redirect): string
    {
        return wp_validate_redirect($redirect, admin_url());
    }

    private function challengeUrl(): string
    {
        return add_query_arg('action', 'ersaal_otp', site_url('wp-login.php', 'login_post'));
    }

    private function redirectToChallenge(): void
    {
        wp_safe_redirect($this->challengeUrl());
        exit;
    }
}
