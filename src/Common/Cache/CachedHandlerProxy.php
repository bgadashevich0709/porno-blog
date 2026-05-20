<?php

declare(strict_types=1);

namespace App\Common\Cache;

final readonly class CachedHandlerProxy
{
    public function __construct(
        private object         $service,
        private CacheInterface $cache,
        private int            $ttl = 86400,
        private array          $tags = []
    ) {}

    /**
     * Магический перехватчик вызовов всех методов
     */
    public function __call(string $method, array $arguments): mixed
    {
        $serviceClass = get_class($this->service);
        $argsHash = !empty($arguments) ? md5(serialize($arguments)) : 'no_args';
        $cacheKey = str_replace('\\', '_', "{$serviceClass}::{$method}:{$argsHash}");

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $result = call_user_func_array([$this->service, $method], $arguments);

        $this->cache->set($cacheKey, $result, $this->ttl, $this->tags);

        return $result;
    }
}
