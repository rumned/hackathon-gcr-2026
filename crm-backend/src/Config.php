<?php

declare(strict_types=1);

namespace App;

final class Config
{
    private static array $values = [];

    public static function set(array $values): void
    {
        self::$values = $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? $default;
    }
}
