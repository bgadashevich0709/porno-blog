<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Common\Container\Container;
use App\Common\Exception\GlobalExceptionHandler;
use App\Common\Http\Request;
use App\Common\Middleware\GlobalSecurityMiddleware;
use App\Common\Response\Startegy\SmartyStrategy;
use App\Common\Router\Router;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\Storage\SessionVisitStorage;
use App\Common\Tracking\VisitStorageInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

$container = new Container();

$container->set(SmartyStrategy::class, static fn() => new SmartyStrategy());

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

$request = Request::createFromGlobals();

$router->dispatch($request);
