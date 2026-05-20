<?php

namespace App\UseCase\Controller\Category\Dto;

use App\Application\Dto\CategoryDto;
use App\Application\Dto\LimitControlDto;
use App\Application\Dto\SortPanelDto;
use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Pagination\Pager;
use JsonSerializable;

class CategoryDataDto implements JsonSerializable
{
    public function __construct(
        public CategoryDto $category,
        public PaginateDto $postsData,
        public Pager $pager,
        public array $breadcrumbs,
        public SortPanelDto $sortPanel,
        public LimitControlDto $limitControl
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'category'  => $this->category,
            'postsData' => $this->postsData,
        ];
    }
}
