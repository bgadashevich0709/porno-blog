<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Listener;

use App\Common\Cache\CacheInterface;
use App\Common\Event\EventInterface;
use App\Common\Event\ListenerInterface;
use App\Modules\Blog\UseCase\Event\CategoryUpdatedEvent;
use App\Modules\Blog\UseCase\Event\PostUpdatedEvent;

final readonly class InvalidateCacheListener implements ListenerInterface
{
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function handle(EventInterface $event): void
    {
        if ($event instanceof PostUpdatedEvent) {
            $this->cache->invalidateTags(['posts_list']);
        }

        if ($event instanceof CategoryUpdatedEvent) {
            $this->cache->invalidateTags(['categories_list']);
        }
    }
}
