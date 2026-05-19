<?php

namespace App\Repository;

interface PostRepositoryInterface
{
    public function findLatestPostsForCategories(array $categoryIds, int $limit, int $offset = 0): array;

    public function findPostById(string $id): ?array;

    public function incrementViewsCount(string $id): void;

}
