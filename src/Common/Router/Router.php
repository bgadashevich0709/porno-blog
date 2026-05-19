<?php

namespace App\Common\Router;

use App\Common\Container\Container;
use App\Common\Http\Attribute\MapQueryString;
use App\Common\Http\Context;
use App\Common\Http\Request;
use App\Common\Http\RequestDtoResolver;
use App\Common\Middleware\MiddlewareInterface;
use App\Common\Router\Route\Route;
use App\Common\Validator\Exception\ValidationException;
use ReflectionClass;
use Exception;

class Router
{
    private array $routes = [];
    private array $globalMiddlewares = [];

    public function __construct(
        private readonly Container $container
    ) {}

    public function addGlobalMiddleware(string $middlewareClass): void
    {
        $this->globalMiddlewares[] = $middlewareClass;
    }

    public function registerControllers(array $controllerClasses): void
    {
        foreach ($controllerClasses as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attribute->newInstance();

                    $this->addRoute(
                        httpMethod: $routeAttr->method,
                        path: $routeAttr->path,
                        controller: $controllerClass,
                        method: $method->getName(),
                        middlewares: $routeAttr->middleware,
                        format: $routeAttr->format
                    );
                }
            }
        }
    }

    private function addRoute(
        string $httpMethod,
        string $path,
        string $controller,
        string $method,
        array $middlewares,
        string $format
    ): void
    {
        $regex = '#^' . preg_replace('#\{[a-zA-Z0-9_]+\}#', '([^/]+)', $path) . '$#';

        $this->routes[] = [
            'httpMethod' => strtoupper($httpMethod),
            'regex'      => $regex,
            'path'       => $path,
            'controller' => $controller,
            'method'     => $method,
            'middleware' => $middlewares,
            'format' => $format
        ];
    }

    public function dispatch(Request $request): void
    {
        // Отрезаем от URL всё лишнее, оставляем только чистый путь (без знаков вопроса и GET-параметров)
        $path = $request->getPathInfo();
        $requestMethod = $request->method;

        // Идем перебором по всем зарегистрированным роутам
        foreach ($this->routes as $route) {
            // Если метод не совпал (например, ищем GET, а роут для POST), сразу идем дальше
            if ($route['httpMethod'] !== $requestMethod) {
                continue;
            }

            // Проверяем URL на соответствие регулярному выражению роута
            if (preg_match($route['regex'], $path, $matches)) {
                // Убираем первый элемент из результатов, так как там лежит весь совпавший текст,
                // нам же нужны только чистые переменные (например, ID категории)
                array_shift($matches);

                // Создаем контекст запроса, который будет передаваться между мидлварами
                $context = new Context($path, $requestMethod);

                /**
                 * Это ядро нашего конвейера (самый глубокий слой).
                 * Этот код сработает только тогда, когда запрос успешно пройдет все проверки мидлваров.
                 * @throws \ReflectionException
                 */
                $coreControllerAction = function (Context $ctx) use ($route, $matches, $request) {
                    // Достаем готовый объект контроллера из DI-контейнера вместе со всеми его зависимостями
                    $controllerInstance = $this->container->get($route['controller']);

                    // Сканируем метод контроллера, чтобы понять, какие аргументы он ждет на вход
                    $reflectionMethod = new \ReflectionMethod($route['controller'], $route['method']);
                    $finalArguments = [];
                    $matchIndex = 0;

                    // Смотрим на каждый параметр метода по очереди
                    foreach ($reflectionMethod->getParameters() as $parameter) {
                        // Проверяем, висит ли над параметром наш атрибут MapQueryString
                        $mapAttr = $parameter->getAttributes(MapQueryString::class);

                        if (!empty($mapAttr)) {
                            // Если атрибут есть, узнаем имя класса DTO из тайп-хинта метода
                            $dtoClass = $parameter->getType()->getName();

                            // Создаем резолвер, который заполнит и проверит DTO
                            $resolver = new RequestDtoResolver($this->container);

                            // Собираем объект DTO и закидываем в массив аргументов для контроллера
                            $finalArguments[] = $resolver->resolve($dtoClass, $request);
                        } else {
                            // Если атрибута нет, значит это обычная переменная (например, string $id).
                            // Берем её значение из тех, что вытащили ранее из URL
                            $finalArguments[] = $matches[$matchIndex] ?? null;
                            $matchIndex++;
                        }
                    }

                    // Запускаем метод контроллера и передаем ему все собранные аргументы
                    $controllerInstance->{$route['method']}(...$finalArguments);
                };

                // Склеиваем глобальные мидлвары (для всего сайта) и локальные (только для этого роута)
                $fullMiddlewareChain = array_merge($this->globalMiddlewares, $route['middleware']);

                // Устанавливаем формат ответа в зависимости от выбранного атрибута в роуте
                $this->setResponseFormat($route);

                /**
                 * Собираем "луковицу" из мидлваров.
                 * Разворачиваем массив задом наперед, чтобы первое мидлваре оказалось самым внешним слоем.
                 * Каждый слой оборачивает предыдущий, передавая ему ссылку на выполнение следующего ($nextLayer).
                 */
                $pipeline = array_reduce(
                    array_reverse($fullMiddlewareChain),
                    function (callable $nextLayer, string $middlewareClass) {
                        return function (Context $ctx) use ($middlewareClass, $nextLayer) {
                            // Достаем мидлваре из контейнера
                            $middlewareInstance = $this->container->get($middlewareClass);

                            // Запускаем его handle, передав текущий контекст и ссылку на следующий слой
                            $middlewareInstance->handle($ctx, $nextLayer);
                        };
                    },
                    $coreControllerAction // Передаем контроллер как самый глубокий внутренний уровень
                );

                // Запускаем получившуюся цепочку. Запрос пойдет гулять по всем мидлварам.
                $pipeline($context);
                return; // Маршрут обработан, выходим из метода
            }
        }

        // Если перебрали все роуты и ничего не подошло — отдаем честную 404 ошибку
        http_response_code(404);
        echo "404 - Страница не найдена";
    }

    private function setResponseFormat(array $route): void
    {
        $format = $route['format'] ?? 'html';
        $_SERVER['ROUTE_FORMAT'] = strtolower($format);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
