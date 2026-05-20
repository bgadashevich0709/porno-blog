<?php

declare(strict_types=1);

namespace App\Common\Config;

final class CacheConfig
{
    public static function getDriver(): string
    {
        return (string) Env::get('CACHE_DRIVER', 'redis');
    }
}
