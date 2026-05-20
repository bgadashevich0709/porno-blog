<?php

declare(strict_types=1);

namespace App\Common\Cache\Driver;

use App\Common\Cache\CacheInterface;
use Predis\ClientInterface;

final class RedisCache implements CacheInterface
{
    public function __construct(
        private readonly ClientInterface $redis,
        private readonly string $prefix,
        private readonly string $tagPrefix
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->prefix . $key);

        if ($value === null) {
            return $default;
        }

        return unserialize($value);
    }

    public function set(string $key, mixed $value, int $ttl = 3600, array $tags = []): bool
    {
        $fullKey = $this->prefix . $key;
        $serialized = serialize($value);

        $this->redis->transaction(function ($tx) use ($fullKey, $serialized, $ttl, $tags) {
            $tx->setex($fullKey, $ttl, $serialized);

            foreach ($tags as $tag) {
                $tagKey = $this->tagPrefix . $tag;
                $tx->sadd($tagKey, [$fullKey]);
                $tx->expire($tagKey, $ttl + 60);
            }
        });

        return true;
    }

    public function delete(string $key): bool
    {
        return (bool) $this->redis->del([$this->prefix . $key]);
    }

    public function invalidateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->tagPrefix . $tag;
            $cacheKeys = $this->redis->smembers($tagKey);

            if (!empty($cacheKeys)) {
                $this->redis->del($cacheKeys);
            }

            $this->redis->del([$tagKey]);
        }
    }

    public function clear(): bool
    {
        $this->clearPattern($this->prefix . '*');
        $this->clearPattern($this->tagPrefix . '*');
        return true;
    }

    private function clearPattern(string $pattern): void
    {
        $cursor = '0';
        do {
            $result = $this->redis->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);

            $cursor = (string) $result[0];
            $keys = $result[1];

            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } while ($cursor !== '0');
    }
}
