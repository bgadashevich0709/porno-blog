<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Router\UrlGenerator;

readonly class PagerUrlGenerator
{
    public function __construct(
        private UrlGenerator $urlGenerator,
        private string       $controller,
        private string       $method,
        private array        $routeParams,
        private array        $queryParams
    ) {}

    public function __invoke(int $pageNumber): string
    {
        $params = $this->queryParams;
        $params['page'] = $pageNumber;

        return $this->urlGenerator->generate($this->controller, $this->method, array_merge($this->routeParams, $params));
    }
}
