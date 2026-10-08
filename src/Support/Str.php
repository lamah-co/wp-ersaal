<?php
declare(strict_types=1);

namespace Ersaal\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Str
{
    /**
     * Get string length safely supporting UTF-8/multibyte without hard dependency on mbstring.
     */
    public static function length(string $string): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($string, 'UTF-8');
        }

        if (function_exists('wp_strlen')) {
            return (int) wp_strlen($string);
        }

        return strlen($string);
    }

    /**
     * Slice string safely supporting UTF-8/multibyte without hard dependency on mbstring.
     */
    public static function substr(string $string, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($string, $start, $length, 'UTF-8');
        }

        if (function_exists('wp_substr')) {
            return (string) wp_substr($string, $start, $length);
        }

        return $length !== null ? (string) substr($string, $start, $length) : (string) substr($string, $start);
    }

    /**
     * Limit the number of characters in a string with a trailing suffix.
     */
    public static function limit(string $string, int $limit = 255, string $end = '...'): string
    {
        if (self::length($string) <= $limit) {
            return $string;
        }

        $endLen = self::length($end);
        $sliceLen = max(0, $limit - $endLen);

        return self::substr($string, 0, $sliceLen) . $end;
    }
}
