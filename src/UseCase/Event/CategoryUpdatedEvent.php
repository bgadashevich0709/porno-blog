<?php

declare(strict_types=1);

namespace App\UseCase\Event;

use App\Common\Event\EventInterface;

final readonly class CategoryUpdatedEvent implements EventInterface
{
    public function __construct(
        public string $categoryId
    ) {}
}
