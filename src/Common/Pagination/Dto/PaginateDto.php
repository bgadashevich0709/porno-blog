<?php

namespace App\Common\Pagination\Dto;

class PaginateDto
{
    /**
     * @param array<mixed> $items Список элементов на текущей странице
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $perPage,
        public int $totalItems,
        public int $totalPages
    ) {}

}
