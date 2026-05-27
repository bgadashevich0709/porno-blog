<?php

declare(strict_types=1);

namespace App\Common\ServiceProvider\ServiceProvider;

use App\Common\Container\Container;
use App\Common\Middleware\GlobalSecurityMiddleware;
use App\Common\Router\Router;
use App\Common\Router\RouteScanner;
use App\Common\Router\UrlGenerator;
use App\Common\ServiceProvider\ServiceProvider;
use App\Common\ServiceProvider\ServiceProviderInterface;

#[ServiceProvider]
class CommonRoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $routeScanner = new RouteScanner();

        $container->set(RouteScanner::class, static fn() => $routeScanner);

        $router = new Router($container, $routeScanner);

        $router->registerControllers('/var/www/src');

        $router->addGlobalMiddleware(GlobalSecurityMiddleware::class);

        $container->set(Router::class, static fn() => $router);

        $compiledRoutes = $router->getRoutes();
        $container->set(UrlGenerator::class, static fn() => new UrlGenerator($compiledRoutes));
    }
}
