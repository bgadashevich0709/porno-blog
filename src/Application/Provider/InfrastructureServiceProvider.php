<?php

declare(strict_types=1);

namespace App\Application\Provider;

use App\Common\Cache\CacheFactory;
use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Exception\GlobalExceptionHandler;
use App\Common\Log\FileLogger;
use App\Common\ServiceProvider\ServiceProviderInterface;
use App\Common\Tracking\Storage\CacheVisitStorage;
//use App\Common\Tracking\Storage\SessionVisitStorage;
use App\Common\Tracking\VisitStorageInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InfrastructureServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(LoggerInterface::class, static fn() => new FileLogger());

        $container->set(GlobalExceptionHandler::class, static function (Container $container) {
            $logger = $container->get(LoggerInterface::class);
            return new GlobalExceptionHandler($logger);
        });

        $exceptionHandler = $container->get(GlobalExceptionHandler::class);
        $exceptionHandler->register();

        $container->set(CacheInterface::class, static fn() => CacheFactory::create());

        $emFactory = static fn() => getEntityManager();
        $container->set(EntityManagerInterface::class, $emFactory);
        $container->set(EntityManager::class, $emFactory);

        // Старая реализация через сессии (закомментирована)
        // $container->set(VisitStorageInterface::class, static fn() => new SessionVisitStorage());

        $container->set(VisitStorageInterface::class, static function (Container $container) {
            $cache = $container->get(CacheInterface::class);

            return new CacheVisitStorage($cache);
        });
    }
}
