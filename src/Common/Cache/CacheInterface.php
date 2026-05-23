<?php

declare(strict_types=1);

namespace App\Common\Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): bool;

    public function delete(string $key): bool;

    public function invalidateTags(array $tags): void;

    public function clear(): bool;
}
