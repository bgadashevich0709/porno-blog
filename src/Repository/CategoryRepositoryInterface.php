<?php

namespace App\Repository;

interface CategoryRepositoryInterface
{
    public function findNonEmptyCategories(): array;

    public function getById(string $id): ?array;
}
