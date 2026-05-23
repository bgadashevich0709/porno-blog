<?php

declare(strict_types=1);

namespace App\Repository;

interface CategoryRepositoryInterface
{
    /**
     * Возвращает список категорий, у которых есть хотя бы один пост.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function findNonEmptyCategories(): array;

    /**
     * Возвращает данные категории по её ID.
     *
     * @return array{id: string, name: string, slug: string}|null
     */
    public function getById(string $id): ?array;
}
