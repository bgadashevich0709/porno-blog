<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Cache\CacheInterface;
use App\Common\Debug\CacheProfiler;
use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Router\UrlGenerator;

/**
 * Архитектурный паттерн: Шаблонный метод (Template Method).
 * Отвечает за высокопроизводительную пагинацию и автоматическое кэширование результатов.
 */
abstract class AbstractIdBasedPaginatedHandler
{
    public function __construct(
        protected readonly UrlGenerator $urlGenerator,
        private readonly CacheInterface $cache
    ) {}

    /**
     * Основной скелет алгоритма пагинации с поддержкой точечного кэширования.
     */
    protected function paginate(PaginationRequestInterface $requestDto, array $context = []): array
    {
        if (!$this->isCacheEnabled($context)) {
            CacheProfiler::logHit(false);

            return $this->executePaginationQuery($requestDto, $context);
        }

        $signatureData = [
            'request'      => $requestDto->toArray(),
            'route_method' => $context['route_method'] ?? 'show',
            'route_params' => $context['route_params'] ?? [],
        ];

        $signature = md5(serialize($signatureData));

        $cacheKey = sprintf(
            '%s_res_%s',
            $context['cache_prefix'] ?? strtolower(basename(str_replace('\\', '/', static::class))),
            $signature
        );

        $paginationResult = $this->cache->get($cacheKey);

        if (!is_array($paginationResult)) {
            CacheProfiler::logHit(false);

            $paginationResult = $this->executePaginationQuery($requestDto, $context);

            $this->cache->set(
                $cacheKey,
                $paginationResult,
                $this->getCacheTtl($context),
                $this->getCacheTags($context)
            );
        } else {
            CacheProfiler::logHit(true);
        }

        return $paginationResult;
    }


    /**
     * Создает генератор URL для постраничной навигации.
     *
     * Использование объекта PagerUrlGenerator вместо \Closure необходимо
     * для успешной сериализации данных пагинации при сохранении в кеш (Redis).
     */
    protected function createUrlGenerator(array $context): PagerUrlGenerator
    {
        $requestDto = $context['requestDto'] ?? null;
        if (!$requestDto instanceof PaginationRequestInterface) {
            throw new \InvalidArgumentException('Context must contain an instance of PaginationRequestInterface.');
        }

        $queryParams = $requestDto->toArray();
        foreach ($queryParams as $key => $value) {
            $queryParams[$key] = match (true) {
                $value instanceof \BackedEnum => $value->value,
                is_object($value) => $value->name ?? (string) $value,
                default => $value,
            };
        }

        return new PagerUrlGenerator(
            $this->urlGenerator,
            $context['route_controller'] ?? throw new \InvalidArgumentException('Missing "route_controller"'),
            $context['route_method'] ?? 'show',
            $context['route_params'] ?? [],
            $queryParams
        );
    }

    protected function isCacheEnabled(array $context): bool
    {
        return true;
    }

    protected function getCacheTtl(array $context): int
    {
        return 300;
    }

    protected function getCacheTags(array $context): array
    {
        return ['posts_list'];
    }

    // ИЗМЕНЕНО: Шаги алгоритма скрыты под protected для соблюдения инкапсуляции
    abstract protected function getTotalCount(array $context): int;
    abstract protected function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, array $context): array;
    abstract protected function fetchFullRowsByIds(array $idList, array $context): array;
    abstract protected function mapRowsToDto(array $rows): array;

    /**
     * Чистая логика выполнения запросов пагинации к СУБД.
     */
    private function executePaginationQuery(PaginationRequestInterface $requestDto, array $context): array
    {
        $totalItems = $this->getTotalCount($context);
        $totalPages = (int) ceil($totalItems / $requestDto->getPerPage());

        if ($totalItems === 0) {
            return [
                'postsData' => new PaginateDto([], $requestDto->getPage(), $requestDto->getPerPage(), 0, 0),
                'pager' => new Pager($this->createUrlGenerator($context), $requestDto->getPerPage()),
            ];
        }

        $offset = max(0, ($requestDto->getPage() - 1) * $requestDto->getPerPage());
        $idList = $this->fetchIds($offset, $requestDto->getPerPage(), $requestDto, $context);
        $rows = $this->fetchFullRowsByIds($idList, $context);
        $mappedItems = $this->mapRowsToDto($rows);

        return [
            'postsData' => new PaginateDto($mappedItems, $requestDto->getPage(), $requestDto->getPerPage(), $totalItems, $totalPages),
            'pager' => new Pager($this->createUrlGenerator($context), $requestDto->getPerPage()),
        ];
    }
}
