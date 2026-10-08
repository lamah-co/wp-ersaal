<?php
declare(strict_types=1);

namespace Ersaal\Support;

/**
 * UTF-8 string helpers that keep SMS handling usable when mbstring is absent.
 */
final class Utf8
{
    public static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $length = preg_match_all('/./us', $value);
        return $length === false ? strlen($value) : $length;
    }

    public static function substr(string $value, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($characters)) {
            return substr($value, $start, $length);
        }

        return implode('', array_slice($characters, $start, $length));
    }
}
