<?php

namespace App\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Get;

#[AsController]
class TestController extends AbstractController
{
    #[Get('/hello-world', middleware: [LoggerMiddleware::class])]
    public function hello(): void
    {
        echo "<h1>Приветики от нового роутера! 🚀</h1>";
        echo "<p>Если ты это видишь, значит сканирование атрибутов работает идеально!</p>";
    }
}
