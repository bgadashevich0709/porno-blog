<?php

declare(strict_types=1);

namespace App\Common\Cache\Driver;

use App\Common\Cache\CacheInterface;

final class MemcachedCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function invalidateTags(array $tags): void
    {
        // Заглушка
    }

    public function clear(): bool
    {
        return true;
    }
}
