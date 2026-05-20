<?php

declare(strict_types=1);

namespace App\Common\Cache\Driver;

use App\Common\Cache\CacheInterface;

final class DatabaseCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed => $default;

    public function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): bool => true;

    public function delete(string $key): bool => true;

    public function invalidateTags(array $tags): void {}

    public function clear(): bool => true;
}
