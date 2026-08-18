<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\Core\Options;
use Ersaal\Services\ConnectionStatusService;

final class OTPAdminController
{
    private Options $options;
    private OTPService $service;

    public function __construct(Options $options, OTPService $service)
    {
        $this->options = $options;
        $this->service = $service;
    }

    public function register(): void
    {
        add_filter('ersaal_settings_tabs', [$this, 'addSettingsTab']);
        add_action('ersaal_render_settings_tab_otp', [$this, 'renderSettingsTab']);
        add_action('admin_menu', [$this, 'addAdminMenus'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_ersaal_otp_test_send', [$this, 'ajaxTestSend']);
        add_action('wp_ajax_ersaal_otp_test_verify', [$this, 'ajaxTestVerify']);
    }

    public function addSettingsTab(array $tabs): array
    {
        $tabs['otp'] = __('OTP', 'ersaal');
        return $tabs;
    }

    public function addAdminMenus(): void
    {
        add_submenu_page(
            'ersaal-dashboard',
            __('OTP Test', 'ersaal'),
            __('OTP Test', 'ersaal'),
            'manage_options',
            'ersaal-otp-test',
            [$this, 'renderTestPage']
        );
        add_submenu_page(
            'ersaal-dashboard',
            __('OTP Activity', 'ersaal'),
            __('OTP Activity', 'ersaal'),
            'manage_options',
            'ersaal-otp-logs',
            [$this, 'renderLogsPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'ersaal-otp-test') === false && strpos($hook, 'ersaal-settings') === false && strpos($hook, 'ersaal-otp-logs') === false) {
            return;
        }
        wp_enqueue_script(
            'ersaal-otp-admin',
            ERSAAL_PLUGIN_URL . 'admin/assets/js/otp-admin.js',
            [],
            ERSAAL_VERSION,
            true
        );
        wp_localize_script('ersaal-otp-admin', 'ersaalOtpAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ersaal_otp_test'),
            'i18n' => [
                'send' => __('Send verification code', 'ersaal'),
                'sending' => __('Sending...', 'ersaal'),
                'verify' => __('Verify code', 'ersaal'),
                'verifying' => __('Verifying...', 'ersaal'),
                'resend' => __('Send a new code', 'ersaal'),
                'wait' => __('You can request a new code in %s seconds.', 'ersaal'),
                'unexpected' => __('The OTP request could not be completed.', 'ersaal'),
                'fields' => [
                    'id' => __('Log ID', 'ersaal'),
                    'action' => __('Action', 'ersaal'),
                    'status' => __('Status', 'ersaal'),
                    'context' => __('Workflow', 'ersaal'),
                    'phone_masked' => __('Phone', 'ersaal'),
                    'reference' => __('Reference', 'ersaal'),
                    'user_id' => __('User ID', 'ersaal'),
                    'api_http_code' => __('HTTP code', 'ersaal'),
                    'error_code' => __('Error code', 'ersaal'),
                    'error_message' => __('Error message', 'ersaal'),
                    'created_at' => __('Created at', 'ersaal'),
                    'updated_at' => __('Updated at', 'ersaal'),
                ],
            ],
        ]);
    }

    public function renderSettingsTab(): void
    {
        $options = $this->options;
        $otpStatus = (new ConnectionStatusService($this->options))->getStatus();
        require ERSAAL_PLUGIN_DIR . 'admin/views/settings-otp.php';
    }

    public function renderTestPage(): void
    {
        require ERSAAL_PLUGIN_DIR . 'admin/views/otp-test.php';
    }

    public function renderLogsPage(): void
    {
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
        $context = isset($_GET['context']) ? sanitize_key(wp_unslash($_GET['context'])) : 'all';
        $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $logsData = $this->service->getLogRepository()->getLogs([
            'status' => $status,
            'context' => $context,
            'page' => $page,
            'per_page' => 20,
        ]);
        require ERSAAL_PLUGIN_DIR . 'admin/views/otp-logs.php';
    }

    public function ajaxTestSend(): void
    {
        $this->authorizeAjax();
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $state = get_transient($this->testTransientKey());
        $now = time();
        if (is_array($state) && !empty($state['expires_at']) && (int) $state['expires_at'] > $now) {
            $retry = max(1, (int) $state['expires_at'] - $now);
            wp_send_json_error([
                'message' => sprintf(__('Use the active code or wait %s seconds before requesting another.', 'ersaal'), $retry),
                'retry_after' => $retry,
                'can_verify' => true,
            ], 429);
        }

        $result = $this->service->initiate($phone, 'admin_test', ['user_id' => get_current_user_id()]);
        if (!$result->isSuccess()) {
            wp_send_json_error([
                'message' => $result->getErrorMessage(),
                'status' => $result->getStatus(),
                'retry_after' => $result->getRetryAfter(),
                'can_verify' => false,
            ], $this->safeHttpStatus($result));
        }

        $expiresIn = (int) ($result->getExpiresIn() ?: $this->service->getConfiguredLifetimeSeconds());
        set_transient($this->testTransientKey(), [
            'reference' => $result->getReference(),
            'phone' => $phone,
            'expires_at' => $now + $expiresIn,
        ], $expiresIn + (5 * MINUTE_IN_SECONDS));

        $validator = new OTPValidator();
        wp_send_json_success([
            'message' => sprintf(__('A verification code was sent to %s.', 'ersaal'), $validator->maskPhone($validator->normalizePhone($phone))),
            'expires_in' => $expiresIn,
        ]);
    }

    public function ajaxTestVerify(): void
    {
        $this->authorizeAjax();
        $state = get_transient($this->testTransientKey());
        if (!is_array($state) || empty($state['reference']) || empty($state['phone'])) {
            wp_send_json_error(['message' => __('This OTP test has expired. Send a new code.', 'ersaal')], 410);
        }
        $code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
        $result = $this->service->verify(
            (string) $state['reference'],
            $code,
            'admin_test',
            ['user_id' => get_current_user_id(), 'phone' => (string) $state['phone']]
        );
        unset($code);

        if (!$result->isSuccess()) {
            if ($result->getStatus() === 'expired') {
                $state['expires_at'] = time();
                set_transient($this->testTransientKey(), $state, 5 * MINUTE_IN_SECONDS);
            }
            wp_send_json_error([
                'message' => $result->getErrorMessage(),
                'status' => $result->getStatus(),
            ], $this->safeHttpStatus($result));
        }

        delete_transient($this->testTransientKey());
        wp_send_json_success(['message' => __('OTP verified successfully. The code cannot be used again.', 'ersaal')]);
    }

    private function authorizeAjax(): void
    {
        check_ajax_referer('ersaal_otp_test', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You are not allowed to test OTP.', 'ersaal')], 403);
        }
    }

    private function testTransientKey(): string
    {
        return 'ersaal_otp_test_' . get_current_user_id();
    }

    private function safeHttpStatus(OTPResult $result): int
    {
        $status = $result->getHttpStatus();
        return $status >= 400 && $status <= 599 ? $status : 400;
    }
}
