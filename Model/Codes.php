<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\GiftCard\Model;

/**
 * Card codes: 16 characters in four groups (ABCD-EFGH-JKMN-PQRS), from letters
 * and digits that can't be mistaken for one another (no 0/O, 1/I/L). Typed
 * codes are read leniently: any case, with or without dashes or spaces.
 */
class Codes
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $chars = '';
        for ($i = 0; $i < 16; $i++) {
            $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return self::format($chars);
    }

    public static function normalize(string $typed): string
    {
        $chars = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $typed));

        return strlen($chars) === 16 ? self::format($chars) : $chars;
    }

    /** For lists: the last group only. */
    public static function mask(string $code): string
    {
        return '••••-' . substr($code, -4);
    }

    private static function format(string $chars): string
    {
        return implode('-', str_split($chars, 4));
    }
}
