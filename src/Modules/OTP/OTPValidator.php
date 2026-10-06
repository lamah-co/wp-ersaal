<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

final class OTPValidator
{
    public function normalizePhone(string $phone): string
    {
        return (new \Ersaal\Services\PhoneValidator())->normalize($phone);
    }

    public function normalizeContext(string $context): string
    {
        $context = sanitize_key($context);
        if ($context === '' || strlen($context) > 50) {
            throw new \InvalidArgumentException(esc_html__('Invalid OTP context.', 'ersaal'));
        }
        return $context;
    }

    public function validateReference(string $reference): string
    {
        $reference = sanitize_text_field(trim($reference));
        if ($reference === '' || strlen($reference) > 100) {
            throw new \InvalidArgumentException(esc_html__('Invalid or missing OTP reference.', 'ersaal'));
        }
        return $reference;
    }

    public function validateCode(string $code): string
    {
        $code = trim($code);
        if (!preg_match('/^[0-9]{4,6}$/', $code)) {
            throw new \InvalidArgumentException(esc_html__('Enter the verification code sent to your phone.', 'ersaal'));
        }
        return $code;
    }

    public function maskPhone(string $phone): string
    {
        return (new \Ersaal\Services\PhoneValidator())->mask($phone);
    }

    public function fingerprintPhone(string $phone): string
    {
        return (new \Ersaal\Services\PhoneValidator())->fingerprint($phone);
    }
}
