<?php

namespace App\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Middleware\ProfilerMiddleware;
use App\Common\Router\Route\Get;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly HomePageIndexHandlerInterface $homepageIndexHandler,
    ) {
        parent::__construct();
    }

    #[Get('/', middleware: [LoggerMiddleware::class, ProfilerMiddleware::class])]
    public function index(): void
    {
        $this->render('index.tpl', [
            'title' => 'Главная страница блога',
            'data' => $this->homepageIndexHandler->getHomepageData(),
        ]);
    }
}
