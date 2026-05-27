<?php

namespace App\Common\Router\Route;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Delete extends Route
{
    public function __construct(string $path, array $middleware = [], string $format = 'html')
    {
        parent::__construct($path, 'DELETE', $middleware, $format);
    }
}
