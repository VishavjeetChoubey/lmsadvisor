<?php
declare(strict_types=1);

namespace App\Helpers;

class Sanitizer
{
    public static function email(string $value): string
    {
        return strtolower(trim(filter_var($value, FILTER_SANITIZE_EMAIL)));
    }

    public static function string(string $value, int $maxLen = 255): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLen);
    }

    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
}
