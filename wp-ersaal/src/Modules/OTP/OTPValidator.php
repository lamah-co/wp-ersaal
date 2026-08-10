<?php
declare(strict_types=1);

namespace Ersaal\Modules\OTP;

final class OTPValidator
{
    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[\s\-().]/', '', $phone) ?? '';
        if (strpos($phone, '00') === 0) {
            $phone = '+' . substr($phone, 2);
        }
        if (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            throw new \InvalidArgumentException(__('Enter a valid phone number including the country code.', 'ersaal'));
        }
        return $phone;
    }

    public function normalizeContext(string $context): string
    {
        $context = sanitize_key($context);
        if ($context === '' || strlen($context) > 50) {
            throw new \InvalidArgumentException(__('Invalid OTP context.', 'ersaal'));
        }
        return $context;
    }

    public function validateReference(string $reference): string
    {
        $reference = sanitize_text_field(trim($reference));
        if ($reference === '' || strlen($reference) > 100) {
            throw new \InvalidArgumentException(__('Invalid or missing OTP reference.', 'ersaal'));
        }
        return $reference;
    }

    public function validateCode(string $code): string
    {
        $code = trim($code);
        if (!preg_match('/^[0-9]{4,6}$/', $code)) {
            throw new \InvalidArgumentException(__('Enter the verification code sent to your phone.', 'ersaal'));
        }
        return $code;
    }

    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $visible = substr($digits, -4);
        return str_repeat('*', max(4, min(8, strlen($digits) - 4))) . $visible;
    }

    public function fingerprintPhone(string $phone): string
    {
        return hash_hmac('sha256', $phone, wp_salt('auth'));
    }
}
