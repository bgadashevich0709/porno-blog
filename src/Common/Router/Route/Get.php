<?php

namespace App\Common\Router\Route;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Get extends Route
{
    // Добавили string $format = 'html' в параметры конструктора
    public function __construct(string $path, array $middleware = [], string $format = 'html')
    {
        parent::__construct($path, 'GET', $middleware, $format);
    }
}
