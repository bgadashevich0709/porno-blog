<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Cache\CacheInterface;
use App\Common\Debug\CacheProfiler;
use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Router\UrlGenerator;

abstract class AbstractIdBasedPaginatedHandler
{
    public function __construct(
        protected readonly UrlGenerator $urlGenerator,
        private readonly CacheInterface $cache
    ) {}

    protected function paginate(PaginationRequestInterface $requestDto, array $context = []): array
    {
        $context['requestDto'] = $requestDto;

        if (!$this->isCacheEnabled($context)) {
            CacheProfiler::logHit(false);
            $paginateDto = $this->executePaginationQuery($requestDto, $context);
        } else {
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

            $paginateDto = $this->cache->get($cacheKey);

            if (!$paginateDto instanceof PaginateDto) {
                CacheProfiler::logHit(false);

                $paginateDto = $this->executePaginationQuery($requestDto, $context);

                $this->cache->set(
                    $cacheKey,
                    $paginateDto,
                    $this->getCacheTtl($context),
                    $this->getCacheTags($context)
                );
            } else {
                CacheProfiler::logHit(true);
            }
        }

        $pager = new Pager($this->createUrlGenerator($context));

        return [
            'postsData' => $paginateDto,
            'pages'     => $pager->generate($paginateDto),
        ];
    }

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

    abstract protected function getTotalCount(array $context): int;
    abstract protected function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, array $context): array;
    abstract protected function fetchFullRowsByIds(array $idList, array $context): array;
    abstract protected function mapRowsToDto(array $rows): array;

    private function executePaginationQuery(PaginationRequestInterface $requestDto, array $context): PaginateDto
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
}
