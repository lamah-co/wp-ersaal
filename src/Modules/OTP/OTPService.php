<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

use Ersaal\API\Exceptions\ApiException;
use Ersaal\API\Exceptions\AuthenticationException;
use Ersaal\API\Exceptions\BalanceException;
use Ersaal\API\Exceptions\ConnectionException;
use Ersaal\API\Exceptions\RateLimitException;
use Ersaal\API\Exceptions\ServerException;
use Ersaal\API\Exceptions\ValidationException;
use Ersaal\Core\Options;

final class OTPService
{
    private Options $options;
    private OTPClient $client;
    private OTPValidator $validator;
    private OTPLogRepository $logs;

    public function __construct(Options $options, OTPClient $client, OTPValidator $validator, OTPLogRepository $logs)
    {
        $this->options = $options;
        $this->client = $client;
        $this->validator = $validator;
        $this->logs = $logs;
    }

    public function initiate(string $phone, string $context = 'custom', array $metadata = []): OTPResult
    {
        try {
            if (!$this->isEnabled()) {
                return OTPResult::failed('disabled', 'otp_disabled', __('OTP is disabled in Ersaal settings.', 'ersaal'));
            }
            $phone = $this->validator->normalizePhone($phone);
            $context = $this->validator->normalizeContext($context);
        } catch (\InvalidArgumentException $e) {
            return OTPResult::failed('invalid', 'invalid_request', $e->getMessage(), 422);
        }

        $masked = $this->validator->maskPhone($phone);
        $fingerprint = $this->validator->fingerprintPhone($phone);
        $expiration = $this->getExpiration();
        $safeMetadata = $this->safeMetadata($metadata, $masked);
        do_action('ersaal_otp_before_initiate', $context, $safeMetadata);

        try {
            $response = $this->client->initiate([
                'receiver' => $phone,
                'sender' => (string) $this->options->get('otp_sender', ''),
                'length' => $this->getLength(),
                'expiration' => $expiration,
                'lang' => $this->getLanguage(),
                'payment_type' => $this->getPaymentType(),
            ], wp_generate_uuid4());

            $reference = sanitize_text_field((string) $response->get('request_id', ''));
            if ($reference === '') {
                throw new ServerException(__('Ersaal did not return an OTP reference.', 'ersaal'), 500);
            }
            $apiExpiresIn = $response->get('expires_in');
            $apiExpiresIn = is_numeric($apiExpiresIn) && (int) $apiExpiresIn > 0 ? (int) $apiExpiresIn : null;
            $result = OTPResult::sent($reference, $response->get('cost'), $apiExpiresIn);
            $this->writeLog('initiate', 'sent', $fingerprint, $masked, $reference, $context, $metadata, 200);
            do_action('ersaal_otp_initiated', $result->toArray(), $context, $safeMetadata);
            return $result;
        } catch (\Throwable $e) {
            $result = $this->mapException($e, 'initiate');
            $this->writeLog('initiate', $result->getStatus(), $fingerprint, $masked, null, $context, $metadata, $result->getHttpStatus(), $result->getErrorCode(), $result->getErrorMessage());
            do_action('ersaal_otp_failed', $result->toArray(), $context, $safeMetadata);
            return $result;
        }
    }

    public function verify(string $reference, string $code, string $context = 'custom', array $metadata = []): OTPResult
    {
        $phone = isset($metadata['phone']) ? (string) $metadata['phone'] : '';
        try {
            if (!$this->isEnabled()) {
                return OTPResult::failed('disabled', 'otp_disabled', __('OTP is disabled in Ersaal settings.', 'ersaal'));
            }
            $reference = $this->validator->validateReference($reference);
            $code = $this->validator->validateCode($code);
            $context = $this->validator->normalizeContext($context);
            $phone = $phone !== '' ? $this->validator->normalizePhone($phone) : '';
        } catch (\InvalidArgumentException $e) {
            return OTPResult::failed('invalid', 'invalid_request', $e->getMessage(), 422);
        }

        $masked = $phone !== '' ? $this->validator->maskPhone($phone) : '********';
        $fingerprint = $phone !== '' ? $this->validator->fingerprintPhone($phone) : hash_hmac('sha256', $reference, wp_salt('auth'));
        $safeMetadata = $this->safeMetadata($metadata, $masked);

        try {
            $this->client->verify($reference, $code);
            // The code is intentionally not retained or passed to any hook.
            unset($code);
            $result = OTPResult::verified();
            $this->writeLog('verify', 'verified', $fingerprint, $masked, $reference, $context, $metadata, 200);
            do_action('ersaal_otp_verified', $result->toArray(), $context, $safeMetadata);
            return $result;
        } catch (\Throwable $e) {
            unset($code);
            $result = $this->mapException($e, 'verify');
            $this->writeLog('verify', $result->getStatus(), $fingerprint, $masked, $reference, $context, $metadata, $result->getHttpStatus(), $result->getErrorCode(), $result->getErrorMessage());
            do_action('ersaal_otp_failed', $result->toArray(), $context, $safeMetadata);
            return $result;
        }
    }

    public function isEnabled(): bool
    {
        return (bool) $this->options->get('otp_enabled', false);
    }

    public function getLogRepository(): OTPLogRepository
    {
        return $this->logs;
    }

    /**
     * Local workflow lifetime derived from the expiration submitted to Ersaal.
     * This does not imply that the current API response returned expires_in.
     */
    public function getConfiguredLifetimeSeconds(): int
    {
        return $this->getExpiration() * MINUTE_IN_SECONDS;
    }

    private function getLength(): int
    {
        $length = (int) $this->options->get('otp_length', 6);
        return in_array($length, [4, 6], true) ? $length : 6;
    }

    private function getExpiration(): int
    {
        return max(1, min(10, (int) $this->options->get('otp_expiration', 5)));
    }

    private function getPaymentType(): string
    {
        $value = (string) $this->options->get('otp_payment_type', 'wallet');
        return in_array($value, ['wallet', 'subscription'], true) ? $value : 'wallet';
    }

    private function getLanguage(): string
    {
        $value = (string) $this->options->get('otp_language', 'auto');
        if ($value === 'ar' || $value === 'en') {
            return $value;
        }
        return strpos(determine_locale(), 'ar') === 0 ? 'ar' : 'en';
    }

    private function safeMetadata(array $metadata, string $masked): array
    {
        return [
            'phone_masked' => $masked,
            'user_id' => isset($metadata['user_id']) ? absint($metadata['user_id']) : 0,
        ];
    }

    private function writeLog(
        string $action,
        string $status,
        string $fingerprint,
        string $masked,
        ?string $reference,
        string $context,
        array $metadata,
        int $httpStatus = 0,
        string $errorCode = '',
        string $errorMessage = ''
    ): void {
        try {
            $this->logs->create([
                'action' => $action,
                'status' => $status,
                'phone_hash' => $fingerprint,
                'phone_masked' => $masked,
                'reference' => $reference,
                'context' => $context,
                'user_id' => isset($metadata['user_id']) ? absint($metadata['user_id']) : null,
                'api_http_code' => $httpStatus ?: null,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('Ersaal OTP activity log unavailable.');
            }
        }
    }

    private function mapException(\Throwable $e, string $operation): OTPResult
    {
        $http = $e instanceof ApiException ? (int) $e->getCode() : 0;
        $retry = $e instanceof RateLimitException ? $e->getRetryAfterSeconds() : null;
        $message = strtolower($e->getMessage());

        if ($e instanceof RateLimitException) {
            return OTPResult::failed('rate_limited', 'rate_limited', __('Too many OTP requests. Please wait before trying again.', 'ersaal'), $http ?: 429, $retry);
        }
        if ($e instanceof AuthenticationException) {
            return OTPResult::failed('failed', 'authentication_failed', __('OTP authentication failed. Check the Ersaal connection settings.', 'ersaal'), $http ?: 401);
        }
        if ($e instanceof BalanceException || strpos($message, 'balance') !== false) {
            return OTPResult::failed('failed', 'subscription_or_balance', __('OTP is unavailable because the wallet or OTP subscription balance is insufficient.', 'ersaal'), $http ?: 400);
        }
        if ($e instanceof ConnectionException || $e instanceof ServerException) {
            return OTPResult::failed('unavailable', 'service_unavailable', __('The OTP service is temporarily unavailable. Please try again later.', 'ersaal'), $http ?: 503);
        }
        if ($operation === 'verify' && ($http === 404 || strpos($message, 'expired') !== false || strpos($message, 'not found') !== false)) {
            return OTPResult::failed('expired', 'expired', __('The verification code has expired or was already used.', 'ersaal'), $http ?: 404);
        }
        if ($operation === 'verify' && ($http === 400 || strpos($message, 'invalid otp') !== false)) {
            return OTPResult::failed('invalid', 'invalid_code', __('The verification code is incorrect.', 'ersaal'), $http ?: 400);
        }
        if (strpos($message, 'already sent') !== false) {
            return OTPResult::failed('rate_limited', 'active_otp_exists', __('An active verification code already exists for this phone. Use it or wait until it expires.', 'ersaal'), $http ?: 400);
        }
        if (strpos($message, 'sender') !== false) {
            return OTPResult::failed('failed', 'sender_invalid', __('The OTP sender is invalid or unavailable for this phone provider.', 'ersaal'), $http ?: 400);
        }
        if ($e instanceof ValidationException || $e instanceof ApiException) {
            return OTPResult::failed('failed', 'api_error', __('The OTP request could not be completed.', 'ersaal'), $http);
        }
        return OTPResult::failed('failed', 'unexpected_error', __('An unexpected OTP error occurred.', 'ersaal'), $http);
    }
}
