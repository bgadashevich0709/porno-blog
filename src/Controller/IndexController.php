<?php

namespace App\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Router\Route\Get;
use App\UseCase\HomePage\HomePageIndexHandler;
use App\Common\Middleware\LoggerMiddleware;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly HomePageIndexHandler $homepageIndexHandler,
    )
    {
        parent::__construct();
    }

    #[Get('/', middleware: [LoggerMiddleware::class])]
    public function index(): void
    {
        $this->render('index.tpl', [
            'title' => 'Главная страница блога',
            'data' => $this->homepageIndexHandler->getHomepageData()
        ]);
    }
}
