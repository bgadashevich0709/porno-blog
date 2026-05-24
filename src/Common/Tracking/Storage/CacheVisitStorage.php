<?php

declare(strict_types=1);

namespace App\Common\Tracking\Storage;

use App\Common\Cache\CacheInterface;
use App\Common\Tracking\VisitStorageInterface;

final readonly class CacheVisitStorage implements VisitStorageInterface
{
    private const VISIT_TTL = 86400;
    private const KEY_PREFIX = 'page_visit:';

    public function __construct(
        private CacheInterface $cache
    ) {}

    public function hasVisit(string $url, string $ip): bool
    {
        $key = $this->generateKey($url, $ip);

        return (bool) $this->cache->get($key, false);
    }

    public function logVisit(string $url, string $ip): void
    {
        $key = $this->generateKey($url, $ip);

        $this->cache->set($key, true, self::VISIT_TTL);
    }

    private function generateKey(string $url, string $ip): string
    {
        return self::KEY_PREFIX . md5($url . ':' . $ip);
    }
}
