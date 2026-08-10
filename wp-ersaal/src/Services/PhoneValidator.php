<?php
declare(strict_types=1);

namespace Ersaal\Services;

class PhoneValidator
{
    public const PROVIDER_ALMADAR = 'almadar';
    public const PROVIDER_LIBYANA = 'libyana';

    /**
     * Normalizes and validates a Libyan mobile number.
     * Expected Libyan prefixes: 091, 092, 093, 094 followed by 7 digits.
     * Output format: 002189XXXXXXXX (14 digits)
     *
     * @param string $phone The input phone number
     * @return string The normalized phone number
     * @throws \InvalidArgumentException If the phone number is not a valid Libyan mobile.
     */
    public function normalize(string $phone): string
    {
        $clean = preg_replace('/[\s\-().]/', '', $phone) ?? '';
        $clean = ltrim($clean, '+');

        if (strpos($clean, '002189') === 0) {
            $clean = '0' . substr($clean, 5);
        } elseif (strpos($clean, '2189') === 0) {
            $clean = '0' . substr($clean, 3);
        }

        if (!preg_match('/^09[1-4][0-9]{7}$/', $clean)) {
            throw new \InvalidArgumentException(__('Invalid phone number. Enter a Libyana or Almadar mobile number starting with 091, 092, 093, or 094.', 'ersaal'));
        }

        return '00218' . substr($clean, 1);
    }

    public function mask(string $phone): string
    {
        $display = preg_replace('/^00218/', '+218', $phone) ?? $phone;
        $digits = preg_replace('/\D/', '', $display) ?? '';
        $length = strlen($digits);

        if ($length < 8) {
            return str_repeat('*', max(4, $length));
        }

        $prefixLength = max(1, $length - 7);
        $prefix = substr($digits, 0, $prefixLength);
        $suffix = substr($digits, -4);

        return ($display !== '' && $display[0] === '+' ? '+' : '') . $prefix . ' *** ' . $suffix;
    }

    public function provider(string $phone): string
    {
        $normalized = $this->normalize($phone);
        $networkDigit = $normalized[6];

        return in_array($networkDigit, ['1', '3'], true)
            ? self::PROVIDER_ALMADAR
            : self::PROVIDER_LIBYANA;
    }

    public function fingerprint(string $phone): string
    {
        return hash_hmac('sha256', $phone, wp_salt('auth'));
    }
}
