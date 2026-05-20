<?php

declare(strict_types=1);

namespace App\Common\Config;

final class RedisConfig
{
    public static function getConnectionParams(): array
    {
        return [
            'scheme'     => (string) Env::get('REDIS_SCHEME'),
            'host'       => (string) Env::get('REDIS_HOST'),
            'port'       => (int) Env::get('REDIS_PORT'),
            'prefix'     => (string) Env::get('REDIS_PREFIX'),
            'tag_prefix' => (string) Env::get('REDIS_TAG_PREFIX'),
        ];
    }
}
