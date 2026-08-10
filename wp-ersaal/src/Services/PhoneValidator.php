<?php
declare(strict_types=1);

namespace Ersaal\Services;

class PhoneValidator
{
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
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $length = strlen($digits);
        
        if ($length < 8) {
            return str_repeat('*', max(4, $length));
        }
        
        $prefixLength = max(1, $length - 7);
        $prefix = substr($digits, 0, $prefixLength);
        $suffix = substr($digits, -4);
        
        return ($phone !== '' && $phone[0] === '+' ? '+' : '') . $prefix . ' *** ' . $suffix;
    }

    public function fingerprint(string $phone): string
    {
        return hash_hmac('sha256', $phone, wp_salt('auth'));
    }
}
