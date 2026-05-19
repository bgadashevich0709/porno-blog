<?php

namespace App\Common\Pagination;

use App\Common\Pagination\Dto\PageDto;
use App\Common\Pagination\Dto\PaginateDto;

class Pager
{
    /**
     * @param callable $urlGenerator Функция, принимающая (int $page) и возвращающая строку URL
     */
    public function __construct(
        private $urlGenerator,
        private readonly int $slidingRange = 2
    ) {
        if (!is_callable($this->urlGenerator)) {
            throw new \InvalidArgumentException('Параметр urlGenerator должен быть callable.');
        }
    }

    /**
     * Преобразует PaginateDto в массив объектов PageDto с жестким лимитом
     * @return array<PageDto>
     */
    public function generate(PaginateDto $paginateDto): array
    {
        $totalPages = $paginateDto->totalPages;
        $currentPage = $paginateDto->currentPage;

        if ($totalPages <= 1) {
            return [];
        }
        $maxVisiblePages = 10;

        if ($totalPages <= $maxVisiblePages) {
            $start = 1;
            $end = $totalPages;
        } else {
            $start = $currentPage - 4;
            $end = $currentPage + 5;

            if ($start < 1) {
                $end = $end + (1 - $start);
                $start = 1;
            }

            if ($end > $totalPages) {
                $start = $start - ($end - $totalPages);
                $end = $totalPages;
            }
        }

        $pages = [];

        if ($start > 1) {
            $pages[] = $this->createPageDto(1, $currentPage);
            if ($start > 2) {
                $pages[] = new PageDto(label: '...', pageNumber: null, url: null, isCurrent: false, isSeparator: true);
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if (($start > 1 && $i === 1) || ($end < $totalPages && $i === $totalPages)) {
                continue;
            }
            $pages[] = $this->createPageDto($i, $currentPage);
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $pages[] = new PageDto(label: '...', pageNumber: null, url: null, isCurrent: false, isSeparator: true);
            }
            $pages[] = $this->createPageDto($totalPages, $currentPage);
        }

        return $pages;
    }

    private function createPageDto(int $pageNumber, int $currentPage): PageDto
    {
        $isCurrent = ($pageNumber === $currentPage);
        $url = $isCurrent ? null : ($this->urlGenerator)($pageNumber);

        return new PageDto(
            label: (string) $pageNumber,
            pageNumber: $pageNumber,
            url: $url,
            isCurrent: $isCurrent,
            isSeparator: false
        );
    }
}
