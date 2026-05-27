<?php

namespace App\Modules\Blog\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Middleware\ProfilerMiddleware;
use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Get;
use App\Modules\Blog\Application\Service\Meta\MetaService;
use App\Modules\Blog\UseCase\Controller\Post\PostShowHandler;

#[AsController]
class PostController extends AbstractController
{
    public function __construct(
        private readonly PostShowHandler $postShowHandler,
        private readonly MetaService     $metaService,
    ) {
        parent::__construct();
    }

    #[Get('/posts/{id}', middleware: [LoggerMiddleware::class, ProfilerMiddleware::class])]
    public function show(string $id): void
    {
        $data = $this->postShowHandler->getPostShowData($id);
        $meta = $this->metaService->buildMeta($data);


        $this->render('post.tpl', [
            'title' => $data->post->title,
            'meta'  => $meta,
            'data'  => $data,
        ]);
    }
}
