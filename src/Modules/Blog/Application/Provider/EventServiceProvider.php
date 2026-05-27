<?php

declare(strict_types=1);

namespace App\Modules\Blog\Application\Provider;

use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Event\EventDispatcher;
use App\Common\ServiceProvider\ServiceProviderInterface;
use App\Modules\Blog\UseCase\Event\CategoryUpdatedEvent;
use App\Modules\Blog\UseCase\Event\PostUpdatedEvent;
use App\Modules\Blog\UseCase\Listener\InvalidateCacheListener;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(EventDispatcher::class, static function () use ($container) {
            $dispatcher = new EventDispatcher();

            $cacheListener = new InvalidateCacheListener($container->get(CacheInterface::class));

            $dispatcher->addListener(PostUpdatedEvent::class, $cacheListener);
            $dispatcher->addListener(CategoryUpdatedEvent::class, $cacheListener);

            return $dispatcher;
        });
    }
}
