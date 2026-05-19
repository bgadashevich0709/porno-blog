<?php

namespace App\Common\Router;

use InvalidArgumentException;

class UrlGenerator
{
    private array $namedRoutes = [];

    /**
     * Принимает массив роутов из Router->getRoutes()
     */
    public function __construct(array $compiledRoutes)
    {
        foreach ($compiledRoutes as $route) {
            $routeKey = "{$route['controller']}@{$route['method']}";

            // Если экшен обрабатывает и GET, и POST, для ссылки берем первый зарегистрированный путь
            if (!isset($this->namedRoutes[$routeKey])) {
                $this->namedRoutes[$routeKey] = $route['path'];
            }
        }
    }

    /**
     * Генерирует URL на основе Контроллера, метода и параметров.
     */
    public function generate(string $controller, string $method, array $params = []): string
    {
        $routeKey = "{$controller}@{$method}";

        if (!isset($this->namedRoutes[$routeKey])) {
            throw new InvalidArgumentException("No registered route matches action: {$routeKey}");
        }

        $path = $this->namedRoutes[$routeKey];

        foreach ($params as $key => $value) {
            $placeholder = "{" . $key . "}";
            if (str_contains($path, $placeholder)) {
                $path = str_replace($placeholder, urlencode((string)$value), $path);
                unset($params[$key]);
            }
        }

        if (preg_match('#\{[a-zA-Z0-9_]+\}#', $path, $matches)) {
            throw new InvalidArgumentException("Missing required URL parameter: {$matches[0]} for action {$routeKey}");
        }

        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }
}
