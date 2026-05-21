<?php

namespace App\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Middleware\ProfilerMiddleware;
use App\Common\Router\Route\Get;
use App\UseCase\Controller\Post\PostShowHandler;

class PostController extends AbstractController
{
    public function __construct(
        private readonly PostShowHandler $postShowHandler,
    ) {
        parent::__construct();
    }

    #[Get('/posts/{id}', middleware: [LoggerMiddleware::class, ProfilerMiddleware::class])]
    public function show(string $id): void
    {
        $data = $this->postShowHandler->getPostShowData($id);

        $this->render('post.tpl', [
            'title' => $data->post->title,
            'data' => $data,
        ]);
    }
}
