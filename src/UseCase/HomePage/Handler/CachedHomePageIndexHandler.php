<?php

declare(strict_types=1);

namespace App\UseCase\HomePage;

use App\Common\Cache\CacheInterface;
use App\UseCase\HomePage\Dto\HomepageDataDto;

readonly class CachedHomePageIndexHandler implements HomePageIndexHandlerInterface
{
    public function __construct(
        // Внедряем оригинальный хендлер как делегат
        private HomePageIndexHandlerInterface $delegate,
        // Внедряем наш кэш из слоя Common
        private CacheInterface                $cache
    ) {}

    public function getHomepageData(int $postsLimit = 3): HomepageDataDto
    {
        // Ключ кэша зависит от переданного лимита постов
        $cacheKey = "homepage_data_limit_{$postsLimit}";

        // Читаем из кэша
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData instanceof HomepageDataDto) {
            return $cachedData;
        }

        // Если кэша нет — вызываем оригинальный тяжелый метод
        $data = $this->delegate->getHomepageData($postsLimit);

        // Сохраняем результат в Redis на 24 часа с тегами для инвалидации
        $this->cache->set(
            $cacheKey,
            $data,
            86400,
            ['posts_list', 'categories_list']
        );

        return $data;
    }
}
