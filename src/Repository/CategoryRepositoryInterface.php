<?php

namespace App\Repository;

interface CategoryRepositoryInterface
{
    public function findNonEmptyCategories(): array;
}