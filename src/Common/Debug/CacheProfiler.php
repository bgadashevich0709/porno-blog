<?php

declare(strict_types=1);

namespace App\Common\Debug;

class CacheProfiler
{
    private static bool $isHit = false;

    public static function logHit(bool $status): void
    {
        self::$isHit = $status;
    }

    public static function isHit(): bool
    {
        return self::$isHit;
    }
}
