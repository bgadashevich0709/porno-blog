<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Cache\CacheInterface;
use App\Common\Debug\CacheProfiler;
use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Pagination\Dto\PaginationContext;
use App\Common\Router\UrlGenerator;

abstract class AbstractIdBasedPaginatedHandler
{
    public function __construct(
        protected readonly UrlGenerator $urlGenerator,
        private readonly CacheInterface $cache
    ) {}

    protected function paginate(PaginationRequestInterface $requestDto, PaginationContext $context): array
    {
        $cacheKey = $this->generateCacheKey($requestDto, $context);
        $paginateDto = $this->cache->get($cacheKey);

        if (!$paginateDto instanceof PaginateDto) {
            CacheProfiler::logHit(false);

            $paginateDto = $this->executePaginationQuery($requestDto, $context);

            $this->cache->set(
                $cacheKey,
                $paginateDto,
                $context->cacheTtl,
                $context->cacheTags
            );
        } else {
            CacheProfiler::logHit(true);
        }

        $pager = new Pager($this->createUrlGenerator($requestDto, $context));

        return [
            'postsData' => $paginateDto,
            'pages'     => $pager->generate($paginateDto),
        ];
    }

    private function generateCacheKey(PaginationRequestInterface $requestDto, PaginationContext $context): string
    {
        $prefix = $context->cachePrefix ?? strtolower(basename(str_replace('\\', '/', static::class)));

        $queryParams = $requestDto->toArray();
        ksort($queryParams);

        $signatureData = [
            'request'      => $queryParams,
            'route_method' => $context->routeMethod,
            'route_params' => $context->routeParams,
        ];

        return sprintf('%s_res_%s', $prefix, md5(json_encode($signatureData, JSON_THROW_ON_ERROR)));
    }

    private function createUrlGenerator(PaginationRequestInterface $requestDto, PaginationContext $context): PagerUrlGenerator
    {
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
            $context->routeController,
            $context->routeMethod,
            $context->routeParams,
            $queryParams
        );
    }

    private function executePaginationQuery(PaginationRequestInterface $requestDto, PaginationContext $context): PaginateDto
    {
        $totalItems = $this->getTotalCount($context);
        $totalPages = (int) ceil($totalItems / $requestDto->getPerPage());

        if ($totalItems === 0) {
            return new PaginateDto([], $requestDto->getPage(), $requestDto->getPerPage(), 0, 0);
        }

        $offset = max(0, ($requestDto->getPage() - 1) * $requestDto->getPerPage());
        $idList = $this->fetchIds($offset, $requestDto->getPerPage(), $requestDto, $context);
        $rows = $this->fetchFullRowsByIds($idList, $context);
        $mappedItems = $this->mapRowsToDto($rows);

        return new PaginateDto($mappedItems, $requestDto->getPage(), $requestDto->getPerPage(), $totalItems, $totalPages);
    }

    abstract protected function getTotalCount(PaginationContext $context): int;
    abstract protected function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, PaginationContext $context): array;
    abstract protected function fetchFullRowsByIds(array $idList, PaginationContext $context): array;
    abstract protected function mapRowsToDto(array $rows): array;
}
