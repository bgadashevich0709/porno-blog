<?php

declare(strict_types=1);

namespace App\Application\Provider;

use App\Common\Container\Container;
use App\Common\Middleware\GlobalSecurityMiddleware;
use App\Common\Router\Router;
use App\Common\Router\UrlGenerator;
use App\Common\ServiceProvider\ServiceProviderInterface;

class RoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $router = new Router($container);

        // Старая реализация через ручной массив в конфиге (закомментирована)
        // $controllersConfiguration = require __DIR__ . '/../../../config/routes.php';
        // $router->registerControllers($controllersConfiguration);

        // Новая реализация:
        $router->registerControllers('/var/www/src');

        $router->addGlobalMiddleware(GlobalSecurityMiddleware::class);

        $container->set(Router::class, static fn() => $router);

        $compiledRoutes = $router->getRoutes();
        $container->set(UrlGenerator::class, static fn() => new UrlGenerator($compiledRoutes));
    }
}
