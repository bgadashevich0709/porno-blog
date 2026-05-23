<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Common\Container\Container;
use App\Common\Http\Request;
use App\Common\Router\Router;
use App\Application\Provider\InfrastructureServiceProvider;
use App\Application\Provider\RoutingServiceProvider;
use App\Application\Provider\BusinessLogicServiceProvider;
use App\Application\Provider\EventServiceProvider;

$container = new Container();

$providers = [
    new InfrastructureServiceProvider(),
    new RoutingServiceProvider(),
    new BusinessLogicServiceProvider(),
    new EventServiceProvider(),
];

foreach ($providers as $provider) {
    $provider->register($container);
}

$request = Request::createFromGlobals();
$container->get(Router::class)->dispatch($request);
