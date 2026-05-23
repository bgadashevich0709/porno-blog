<?php

declare(strict_types=1);

namespace App\Application\Provider;

use App\Common\Cache\CacheFactory;
use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Exception\GlobalExceptionHandler;
use App\Common\ServiceProvider\ServiceProviderInterface;
use App\Common\Tracking\Storage\SessionVisitStorage;
use App\Common\Tracking\VisitStorageInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class InfrastructureServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $exceptionHandler = new GlobalExceptionHandler();
        $exceptionHandler->register();

        $container->set(CacheInterface::class, static fn() => CacheFactory::create());

        $emFactory = static fn() => getEntityManager();
        $container->set(EntityManagerInterface::class, $emFactory);
        $container->set(EntityManager::class, $emFactory);

        $container->set(VisitStorageInterface::class, static fn() => new SessionVisitStorage());
    }
}
