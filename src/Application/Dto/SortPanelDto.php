<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class SortPanelDto
{
    public function __construct(
        public array  $sortOptions,
        public array  $wayOptions,
        public string $currentSort,
        public string $currentWay,
        public string $sortKeyName
    ) {}
}
