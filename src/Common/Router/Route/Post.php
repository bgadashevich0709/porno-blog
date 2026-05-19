<?php

namespace App\Common\Router\Route;

use Attribute;
#[Attribute(Attribute::TARGET_METHOD)]
class Post extends Route
{
    public function __construct(string $path, array $middleware = [])
    {
        parent::__construct($path, 'POST', $middleware);
    }
}