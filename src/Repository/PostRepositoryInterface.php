<?php

namespace App\Repository;

use Doctrine\DBAL\Query\QueryBuilder;

interface PostRepositoryInterface
{
    public function findLatestPostsWithCategories(int $globalLimit = 300): array;

    public function findRelatedPostsByCategories(array $categoryIds, int $limit): array;

    public function findPostById(string $id): ?array;

    public function incrementViewsCount(string $id): void;

    public function getIdSubQueryBuilder(string $categoryId, string $sortField, string $sortWay): QueryBuilder;

    public function getCountQueryBuilder(string $categoryId): QueryBuilder;

    public function getPostsByDataQueryBuilder(): QueryBuilder;
}
