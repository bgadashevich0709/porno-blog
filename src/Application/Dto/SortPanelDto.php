<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class SortPanelDto
{
    /**
     * @param array<string, string> $sortOptions Доступные поля (из Enum::labels())
     * @param array<string, string> $wayOptions  Доступные направления (из Enum::labels())
     * @param string $currentSort                Текущий выбранный ключ (name или value)
     * @param string $currentWay                 Текущее направление (name или value)
     * @param string $sortKeyName                Имя GET-параметра (н-р, 'CategorySort')
     */
    public function __construct(
        public array  $sortOptions,
        public array  $wayOptions,
        public string $currentSort,
        public string $currentWay,
        public string $sortKeyName
    ) {}
}
