<?php

namespace App\UseCase\HomePage\Dto;

use App\Application\Dto\CategoryGroupDto;

class HomepageDataDto
{
    /**
     * @param array<CategoryGroupDto> $categories
     */
    public function __construct(
        public array $categories
    ) {}

}
