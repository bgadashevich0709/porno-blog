<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Application\Service\PostDtoFactory;
use App\Common\Cache\CacheFactory;
use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Event\EventDispatcher;
use App\Common\Exception\GlobalExceptionHandler;
use App\Common\Http\Request;
use App\Common\Middleware\GlobalSecurityMiddleware;
use App\Common\Router\Router;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\Storage\SessionVisitStorage;
use App\Common\Tracking\VisitStorageInterface;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\UseCase\Controller\HomePage\Handler\CachedHomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;
use App\UseCase\Event\CategoryUpdatedEvent;
use App\UseCase\Event\PostUpdatedEvent;
use App\UseCase\Listener\InvalidateCacheListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

$container = new Container();

$container->set(CacheInterface::class, static fn() => CacheFactory::create());

$exceptionHandler = new GlobalExceptionHandler();
$exceptionHandler->register();

$emFactory = static fn() => getEntityManager();
$container->set(EntityManagerInterface::class, $emFactory);
$container->set(EntityManager::class, $emFactory);
$container->set(VisitStorageInterface::class, static fn() => new SessionVisitStorage());

$controllersConfiguration = require __DIR__ . '/../config/routes.php';

$router = new Router($container);

$router->registerControllers($controllersConfiguration);
$router->addGlobalMiddleware(GlobalSecurityMiddleware::class);

$compiledRoutes = $router->getRoutes();
$container->set(UrlGenerator::class, static fn() => new UrlGenerator($compiledRoutes));

$container->set(HomePageIndexHandlerInterface::class, static function() use ($container) {
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

$container->set(EventDispatcher::class, static function() use ($container) {
    $dispatcher = new EventDispatcher();

    $cacheListener = new InvalidateCacheListener($container->get(CacheInterface::class));

    $dispatcher->addListener(PostUpdatedEvent::class, $cacheListener);
    $dispatcher->addListener(CategoryUpdatedEvent::class, $cacheListener);

    return $dispatcher;
});

$request = Request::createFromGlobals();

$router->dispatch($request);
