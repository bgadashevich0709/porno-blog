<?php

declare(strict_types=1);

namespace App\Common\ServiceProvider;

use App\Common\Container\Container;

interface ServiceProviderInterface
{
    /**
     * Регистрирует зависимости в DI-контейнере приложения.
     */
    public function register(Container $container): void;
}
