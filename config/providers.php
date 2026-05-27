<?php

declare(strict_types=1);

use App\Modules\Blog\Application\Provider\BusinessLogicServiceProvider;
use App\Modules\Blog\Application\Provider\EventServiceProvider;
use App\Modules\Blog\Application\Provider\InfrastructureServiceProvider;
use App\Modules\Blog\Application\Provider\RoutingServiceProvider;

return [
    new InfrastructureServiceProvider(),
    new RoutingServiceProvider(),
    new BusinessLogicServiceProvider(),
    new EventServiceProvider(),
];
