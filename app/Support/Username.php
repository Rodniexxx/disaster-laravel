<?php

namespace App\Support;

class Username
{
    /** Uppercase word initials only; keep all remaining letters and spaces. */
    public static function capitalizeWords(string $value): ?string
    {
        return preg_replace_callback(
            '/(^|[\p{Z}\t\n\r\f\x0B])(\p{L})/u',
            fn (array $match): string => $match[1].mb_strtoupper($match[2], 'UTF-8'),
            $value,
        );
    }
}
