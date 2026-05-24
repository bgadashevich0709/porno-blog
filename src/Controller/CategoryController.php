<?php

namespace App\Controller;

use App\Application\Service\Meta\MetaService;
use App\Common\Controller\AbstractController;
use App\Common\Http\Attribute\MapQueryString;
use App\Common\Middleware\LoggerMiddleware;
use App\Common\Middleware\ProfilerMiddleware;
use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Get;
use App\UseCase\Controller\Category\CategoryShowHandler;
use App\UseCase\Controller\Category\Dto\CategoryRequestDto;

#[AsController]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryShowHandler $categoryShowHandler,
        private readonly MetaService         $metaService,
    ) {
        parent::__construct();
    }

    /**
     * Отображает информацию о категории.
     *
     * По умолчанию используется HTML-стратегия вывода (SmartyStrategy).
     * Чтобы переключить стратегию ответа, используйте именованный аргумент `format` в атрибуте маршрута.
     * Например: #[Get('/categories/{id}', middleware: [...], format: 'json')] или format: 'xml'.
     * Роутер запишет это значение в $_SERVER['ROUTE_FORMAT'], и фабрика выберет нужный класс ответа.
     */
    //    #[Get('/categories/{id}', middleware: [LoggerMiddleware::class], format: 'json')]
    #[Get('/categories/{id}', middleware: [LoggerMiddleware::class,  ProfilerMiddleware::class])]
    public function show(string $id, #[MapQueryString] CategoryRequestDto $requestDto): void
    {
        $data = $this->categoryShowHandler->getCategoryShowData($id, $requestDto);
        $meta = $this->metaService->buildMeta($data);

        $this->render('category.tpl', [
            'title' => $data->category->title,
            'meta'  => $meta,
            'data'  => $data,
        ]);
    }
}
