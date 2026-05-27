<?php

declare(strict_types=1);

namespace App\Modules\Blog\Application\Dto; // Лежит вместе со всеми DTO приложения

final readonly class MetaDto
{
    public function __construct(
        public string $title,
        public string $description,
        public string $keywords
    ) {}
}
