<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Common\Container\Container;
use App\Common\Http\Request;
use App\Common\Router\Router;
use App\Common\ServiceProvider\ServiceProviderBootstrapper;

define('APP_ROOT', dirname(__DIR__));

$container = new Container();

$bootstrapper = new ServiceProviderBootstrapper(
    scanDirs: [
        APP_ROOT . '/src/Common/ServiceProvider',
        APP_ROOT . '/src/Modules',
    ],
    cacheDir: APP_ROOT . '/var/cache'
);

$bootstrapper->boot($container, isDebug: true);

$request = Request::createFromGlobals();
$container->get(Router::class)->dispatch($request);
