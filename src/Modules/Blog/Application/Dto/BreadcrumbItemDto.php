<?php

declare(strict_types=1);

namespace App\Modules\Blog\Application\Dto;

class BreadcrumbItemDto
{
    public function __construct(
        public string $label,
        public ?string $url = null
    ) {}
}
