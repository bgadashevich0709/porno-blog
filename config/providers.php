<?php

declare(strict_types=1);

use App\Application\Provider\BusinessLogicServiceProvider;
use App\Application\Provider\EventServiceProvider;
use App\Application\Provider\InfrastructureServiceProvider;
use App\Application\Provider\RoutingServiceProvider;

return [
    new InfrastructureServiceProvider(),
    new RoutingServiceProvider(),
    new BusinessLogicServiceProvider(),
    new EventServiceProvider(),
];
