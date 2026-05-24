<?php

namespace App\UseCase\Controller\Category\Dto;

use App\Application\Dto\CategoryDto;
use App\Application\Dto\LimitControlDto;
use App\Application\Dto\SortPanelDto;
use App\Application\Service\Meta\HasMetaInterface;
use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Pagination\Pager;
use JsonSerializable;

class CategoryDataDto implements JsonSerializable, HasMetaInterface
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

    public function getMetaTitle(): string
    {
        return $this->category->getMetaTitle();
    }


    public function getMetaDescription(): string
    {
        return $this->category->getMetaDescription();
    }

    public function getMetaKeywords(): string
    {
        return $this->category->getMetaKeywords();
    }
}
