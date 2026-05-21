<?php

declare(strict_types=1);

namespace App\UseCase\Controller\HomePage\Handler;

use App\Common\Cache\CacheInterface;
use App\Common\Debug\CacheProfiler;
use App\UseCase\Controller\HomePage\Dto\HomepageDataDto;

readonly class CachedHomePageIndexHandler implements HomePageIndexHandlerInterface
{
    private const int CACHE_TTL_SECONDS = 3600;

    /**
     * @param HomePageIndexHandler $delegate
     */
    public function __construct(
        private HomePageIndexHandlerInterface $delegate,
        private CacheInterface                $cache
    ) {}

    public function getHomepageData(int $postsLimit = 3): HomepageDataDto
    {
        $cacheKey = "homepage_data_limit_{$postsLimit}";

        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData instanceof HomepageDataDto) {
            return $cachedData;
        }

        CacheProfiler::logHit(false);

        $data = $this->delegate->getHomepageData($postsLimit);

        $this->cache->set(
            $cacheKey,
            $data,
            self::CACHE_TTL_SECONDS,
            ['posts_list', 'categories_list']
        );

        return $data;
    }
}
