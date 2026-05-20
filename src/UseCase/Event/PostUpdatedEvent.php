<?php

declare(strict_types=1);

namespace App\UseCase\Event;

use App\Common\Event\EventInterface;

final readonly class PostUpdatedEvent implements EventInterface
{
    public function __construct(
        public int $postId
    ) {}
}
