<?php

namespace App\UseCase\Category\Dto;

use App\Application\Enum\CategorySort;
use App\Application\Enum\SortWay;
use App\Common\Pagination\PaginationRequestInterface;
use App\Common\Validator\Constraint\GreaterThanOrEqual as AssertGE;
use App\Common\Validator\Constraint\LessThanOrEqual as AssertLE;

class CategoryRequestDto implements PaginationRequestInterface
{
    public function __construct(
        public CategorySort $CategorySort = CategorySort::views,
        public SortWay $sortWay = SortWay::desc,

        #[AssertGE(1, message: "Номер страницы не может быть меньше 1")]
        #[AssertLE(1000, message: "Максимальный номер страницы — 1000")]
        public int $page = 1,

        #[AssertGE(1, message: "Количество элементов на странице не может быть меньше 1")]
        #[AssertLE(100, message: "Нельзя выводить более 100 элементов за раз")]
        public int $perPage = 12,
    ) {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getSortField(): string
    {
        return $this->CategorySort->value;
    }

    public function getSortWay(): string
    {
        return $this->sortWay->value;
    }

    public function toArray(): array
    {
        return [
            'CategorySort' => $this->CategorySort->value,
            'sortWay'      => $this->sortWay->value,
            'page'         => $this->page,
            'perPage'      => $this->perPage,
        ];
    }
}
