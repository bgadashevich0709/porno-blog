<?php

namespace App\Controller;

use App\Application\Service\Meta\MetaService;
use App\Common\Controller\AbstractController;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Middleware\ProfilerMiddleware;
use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Get;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;

#[AsController]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly HomePageIndexHandlerInterface $homepageIndexHandler,
        private readonly MetaService                  $metaService,
    ) {
        parent::__construct();
    }

    #[Get('/', middleware: [LoggerMiddleware::class, ProfilerMiddleware::class])]
    public function index(): void
    {
        $data = $this->homepageIndexHandler->getHomepageData();
        $meta = $this->metaService->buildMeta($data);

        $this->render('index.tpl', [
            'title' => 'Главная страница блога',
            'meta'  => $meta,
            'data'  => $data,
        ]);
    }
}
