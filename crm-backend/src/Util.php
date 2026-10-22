<?php

declare(strict_types=1);

namespace App;

final class Util
{
    /** Random version 4 UUID, the id format SuiteCRM uses. */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /** Current time in UTC, in the format stored in DATETIME columns. */
    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /** "starts with" pattern for LIKE, with % and _ in the input escaped. */
    public static function likePrefix(string $value): string
    {
        return addcslashes($value, '\\%_') . '%';
    }
}
