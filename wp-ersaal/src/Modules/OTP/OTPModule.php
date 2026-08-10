<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\API\Client;
use Ersaal\Contracts\ModuleInterface;
use Ersaal\Core\Options;

final class OTPModule implements ModuleInterface
{
    private Options $options;
    private OTPService $service;

    public function __construct(Options $options)
    {
        $this->options = $options;
        $this->service = new OTPService(
            $options,
            new OTPClient(new Client($options)),
            new OTPValidator(),
            new OTPLogRepository()
        );
    }

    public function id(): string { return 'otp'; }
    public function isActive(): bool { return true; }

    public function register(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);

        if (is_admin()) {
            (new OTPAdminController($this->options, $this->service))->register();
            (new OTPUserProfile($this->options, $this->service))->register();
        }
    }

    public function boot(): void {}

    public function getService(): OTPService
    {
        return $this->service;
    }

    public function registerSettings(): void
    {
        register_setting('ersaal_otp_settings', 'ersaal_otp_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_login_enabled', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_sender', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_payment_type', [
            'type' => 'string',
            'sanitize_callback' => static function ($value): string {
                return in_array($value, ['wallet', 'subscription'], true) ? (string) $value : 'wallet';
            },
            'default' => 'wallet',
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_length', [
            'type' => 'integer',
            'sanitize_callback' => static function ($value): int {
                return in_array((int) $value, [4, 6], true) ? (int) $value : 6;
            },
            'default' => 6,
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_expiration', [
            'type' => 'integer',
            'sanitize_callback' => static function ($value): int {
                return max(1, min(10, (int) $value));
            },
            'default' => 5,
        ]);
        register_setting('ersaal_otp_settings', 'ersaal_otp_language', [
            'type' => 'string',
            'sanitize_callback' => static function ($value): string {
                return in_array($value, ['auto', 'ar', 'en'], true) ? (string) $value : 'auto';
            },
            'default' => 'auto',
        ]);
    }
}
