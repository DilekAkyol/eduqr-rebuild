<?php

declare(strict_types=1);

namespace EduQR\Support;

final class ShortCode
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // 32 characters (no 0, 1, I, O)

    public static function generate(int $length = 6): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}
