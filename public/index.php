<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Common\Container\Container;
use App\Common\Http\Request;
use App\Common\Router\Router;

$container = new Container();

$providers = require __DIR__ . '/../config/providers.php';

foreach ($providers as $provider) {
    $provider->register($container);
}

$request = Request::createFromGlobals();
$container->get(Router::class)->dispatch($request);
