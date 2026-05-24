<?php

namespace App\Common\Router;

use App\Common\Container\Container;
use App\Common\Http\Attribute\MapQueryString;
use App\Common\Http\Context;
use App\Common\Http\Request;
use App\Common\Http\RequestDtoResolver;
use App\Common\Router\Route\Route;
use App\Common\Validator\Exception\ValidationException;
use App\Exceptions\ResourceNotFoundException;
use ReflectionClass;

/**
 * TODO: Провести рефакторинг класса Router, когда кодовая база проекта разрастется.
 * Текущий класс совмещает слишком много обязанностей (SRP violation).
 *
 * Планируемые шаги по разделению монолита:
 * 1. [ ] Вынести сканирование директорий и парсинг атрибутов #[AsController] в RouteScanner.
 * 2. [ ] Перенести логику автоматической сборки зависимостей (Autowiring) внутрь Container.php.
 * 3. [ ] Оставить в Router только хранение маршрутов, сопоставление URL (matching) и запуск Middleware.
 */
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

    /**
     * @deprecated Метод устарел, так как требует ручной передачи массива классов.
     * Используйте актуальный метод registerControllers(string $dirPath).
     */
    #[\JetBrains\PhpStorm\Deprecated(reason: 'Используйте автоматическое сканирование директорий', replacement: '%class%->registerControllers($dirPath)')]
    public function registerControllersDeprecated(array $controllerClasses): void
    {

        foreach ($controllerClasses as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    // @var Route $routeAttr
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

    /**
     * Универсально и рекурсивно сканирует директорию,
     * находит абсолютно все контроллеры с атрибутом #[AsController].
     */
    public function registerControllers(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $directoryIterator = new \RecursiveDirectoryIterator($dirPath);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $fileMatches) {
            // КРИТИЧЕСКИ ВАЖНО: берем именно нулевой индекс [0] из совпадений регулярки!
            $filePath = $fileMatches[0];

            // Динамически вычисляем класс по стандарту PSR-4
            $controllerClass = $this->resolveClassNameFromFile($filePath);

            if ($controllerClass === null || !class_exists($controllerClass)) {
                continue;
            }

            $reflection = new \ReflectionClass($controllerClass);

            // Если у класса нет маркера #[AsController] — скипаем его
            $hasAsController = !empty($reflection->getAttributes(\App\Common\Router\Attribute\AsController::class));

            if (!$hasAsController) {
                continue;
            }

            // Парсим роуты методов
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

            // Регистрируем в контейнере для автовайринга
            $this->container->set($controllerClass, function (\App\Common\Container\Container $c) use ($controllerClass) {
                $reflectionForContainer = new \ReflectionClass($controllerClass);
                $constructor = $reflectionForContainer->getConstructor();

                if ($constructor === null) {
                    return new $controllerClass();
                }

                $parameters = $constructor->getParameters();
                $dependencies = [];

                foreach ($parameters as $parameter) {
                    $type = $parameter->getType();

                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                        $dependencies[] = $c->get($type->getName());
                    } else {
                        if ($parameter->isDefaultValueAvailable()) {
                            $dependencies[] = $parameter->getDefaultValue();
                        } else {
                            throw new \RuntimeException(
                                "Невозможно разрешить зависимость для {$parameter->getName()} в {$controllerClass}"
                            );
                        }
                    }
                }

                return $reflectionForContainer->newInstanceArgs($dependencies);
            });
        }
    }

    /**
     * Динамически вычисляет PSR-4 неймспейс для любого файла внутри папки src,
     * учитывая, что папка src/Application/Controller мапится на App\Controller
     */
    private function resolveClassNameFromFile(string $filePath): ?string
    {
        $filePath = str_replace('\\', '/', $filePath);

        // Ищем вхождение '/src/' в абсолютном пути
        $srcPosition = strpos($filePath, '/src/');
        if ($srcPosition === false) {
            return null;
        }

        // Отрезаем всё, что до папки src, и убираем расширение .php
        $relativePart = substr($filePath, $srcPosition + 5);
        $relativePart = substr($relativePart, 0, -4);

        // УБИРАЕМ "Application/", если файл лежит внутри папки контроллеров
        if (str_starts_with($relativePart, 'Application/Controller/')) {
            $relativePart = str_replace('Application/Controller/', 'Controller/', $relativePart);
        }

        // Мапим путь на дефолтный корневой неймспейс App\
        return 'App\\' . str_replace('/', '\\', $relativePart);
    }

    /**
     * Преобразует сырые данные маршрута (включая динамические параметры вроде {id})
     * в регулярное выражение и сохраняет всю конфигурацию в общий массив маршрутов.
     *
     * @param string $httpMethod HTTP-метод запроса (GET, POST и т.д.)
     * @param string $path Шаблон URL-пути (например, '/blog/{id}')
     * @param string $controller Полное имя класса контроллера с неймспейсом
     * @param string $method Имя вызываемого метода (экшена) в контроллере
     * @param array $middlewares Массив посредников, назначенных на этот маршрут
     * @param string $format Ожидаемый формат ответа (например, html или json)
     * @return void
     */
    private function addRoute(
        string $httpMethod,
        string $path,
        string $controller,
        string $method,
        array $middlewares,
        string $format
    ): void {
        // Конвертируем динамические параметры роута.
        // Строка вида '/blog/{id}' превратится в регулярное выражение '#^/blog/([^/]+)$#'
        // Это нужно, чтобы роутер потом мог понять, что вместо {id} в URL может быть любое число или текст.
        $regex = '#^' . preg_replace('#\{[a-zA-Z0-9_]+\}#', '([^/]+)', $path) . '$#';


        // Сохраняем всю информацию о маршруте в массив $this->routes
        $this->routes[] = [
            'httpMethod' => strtoupper($httpMethod), // Приводим метод к верхнему регистру (GET, POST)
            'regex'      => $regex,                  // Маска для проверки совпадения URL
            'path'       => $path,                   // Оригинальный путь
            'controller' => $controller,             // Класс контроллера, который за это отвечает
            'method'     => $method,                 // Функция внутри контроллера
            'middleware' => $middlewares,            // Список посредников для этого роута
            'format'     => $format,                 // Формат ответа (html/json)
        ];
    }

    /**
     * @throws \ReflectionException
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
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

        throw new ResourceNotFoundException();
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
