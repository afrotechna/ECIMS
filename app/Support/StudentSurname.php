<?php

namespace App\Support;

/**
 * One-word family name for student portal passwords (last token only).
 */
class StudentSurname
{
    public static function extract(?string ...$nameParts): string
    {
        foreach ($nameParts as $part) {
            $word = self::lastWord($part);
            if ($word !== '') {
                return $word;
            }
        }

        return '';
    }

    public static function lastWord(?string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        if ($text === '') {
            return '';
        }

        if (str_contains($text, ',')) {
            $text = trim(explode(',', $text, 2)[0]);
        }

        $parts = preg_split('/\s+/u', $text) ?: [];

        return (string) (end($parts) ?: '');
    }

    public static function portalPassword(?string ...$nameParts): string
    {
        $surname = self::extract(...$nameParts);
        $password = mb_strtolower($surname, 'UTF-8');

        return $password !== '' ? $password : 'student';
    }
}
