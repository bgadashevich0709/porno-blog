<?php

declare(strict_types=1);

namespace App\Application\Provider;

use App\Application\Service\PostDtoFactory;
use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Router\UrlGenerator;
use App\Common\ServiceProvider\ServiceProviderInterface;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\UseCase\Controller\Category\CategoryShowHandler;
use App\UseCase\Controller\HomePage\Handler\CachedHomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;

class BusinessLogicServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(PostDtoFactory::class, static function () use ($container) {
            return new PostDtoFactory($container->get(\App\Application\Service\ImageService::class));
        });

        $container->set(HomePageIndexHandlerInterface::class, static function () use ($container) {
            $originalHandler = new HomePageIndexHandler(
                $container->get(CategoryRepositoryInterface::class),
                $container->get(PostRepositoryInterface::class),
                $container->get(UrlGenerator::class),
                $container->get(PostDtoFactory::class)
            );

            return new CachedHomePageIndexHandler(
                $originalHandler,
                $container->get(CacheInterface::class)
            );
        });


        $container->set(CategoryShowHandler::class, static function () use ($container) {
            return new CategoryShowHandler(
                $container->get(CategoryRepositoryInterface::class),
                $container->get(PostRepositoryInterface::class),
                $container->get(PostDtoFactory::class),
                $container->get(UrlGenerator::class),
                $container->get(CacheInterface::class)
            );
        });
    }
}
