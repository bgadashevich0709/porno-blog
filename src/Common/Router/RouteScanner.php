<?php

namespace App\Common\Router;

use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use RegexIterator;
use RuntimeException;

/**
 * Класс RouteScanner отвечает исключительно за сканирование файловой системы,
 * обнаружение контроллеров и парсинг их атрибутов маршрутизации (SRP).
 */
class RouteScanner
{
    /**
     * Сканирует директорию и возвращает массив с маршрутами и фабриками для контейнера
     *
     * @param string $dirPath Путь к директории с контроллерами
     * @return array{routes: array, controllers: array<string, callable>}
     */
    public function scan(string $dirPath): array
    {
        $result = [
            'routes'      => [], // Список для добавления в роутер
            'controllers' => [], // Список ленивых фабрик для DI-контейнера
        ];

        if (!is_dir($dirPath)) {
            return $result;
        }

        $directoryIterator = new RecursiveDirectoryIterator($dirPath);
        $iterator = new RecursiveIteratorIterator($directoryIterator);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $fileMatches) {
            // КРИТИЧЕСКИ ВАЖНО: берем именно нулевой индекс [0] из совпадений регулярки!
            $filePath = $fileMatches[0];

            // Динамически вычисляем класс по стандарту PSR-4
            $controllerClass = $this->resolveClassNameFromFile($filePath);

            if ($controllerClass === null || !class_exists($controllerClass)) {
                continue;
            }

            $reflection = new ReflectionClass($controllerClass);

            // Если у класса нет маркера #[AsController] — скипаем его
            $hasAsController = !empty($reflection->getAttributes(AsController::class));

            if (!$hasAsController) {
                continue;
            }

            // Парсим роуты методов
            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    /** @var Route $routeAttr */
                    $routeAttr = $attribute->newInstance();

                    // Сохраняем параметры маршрута в массив
                    $result['routes'][] = [
                        'httpMethod'  => $routeAttr->method,
                        'path'        => $routeAttr->path,
                        'controller'  => $controllerClass,
                        'method'      => $method->getName(),
                        'middlewares' => $routeAttr->middleware,
                        'format'      => $routeAttr->format,
                    ];
                }
            }

            // Формируем ленивую фабрику сборки текущего контроллера для DI-контейнера
            $result['controllers'][$controllerClass] = function (\App\Common\Container\Container $c) use ($controllerClass) {
                // Используем рефлексию, чтобы "просканировать" структуру класса контроллера
                $reflectionForContainer = new ReflectionClass($controllerClass);

                // Получаем информацию о конструкторе класса, чтобы узнать его зависимости
                $constructor = $reflectionForContainer->getConstructor();

                // Если конструктора вообще нет, значит и зависимостей у класса нет.
                // Просто создаем пустой экземпляр через new и сразу возвращаем его.
                if ($constructor === null) {
                    return new $controllerClass();
                }

                // Если конструктор есть, вытаскиваем список всех его аргументов (параметров)
                $parameters = $constructor->getParameters();
                $dependencies = []; // Сюда по порядку будем складывать готовые объекты-зависимости

                // Перебираем каждый параметр конструктора один за другим
                foreach ($parameters as $parameter) {
                    // Узнаем тип текущего аргумента (какой класс или интерфейс там указан)
                    $type = $parameter->getType();

                    // Проверяем: указан ли конкретный класс/интерфейс, и не является ли он базовым типом PHP (типа string, int, array)
                    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                        // Магия автовайринга: берем полное имя класса-зависимости (например, MetaService)
                        // и рекурсивно запрашиваем его у самого контейнера через $c->get()
                        $dependencies[] = $c->get($type->getName());
                    } else {
                        // Если аргумент — это простой тип (строка, число) или класс не указан,
                        // проверяем, задано ли для него дефолтное значение в конструкторе (например, = 'default')
                        if ($parameter->isDefaultValueAvailable()) {
                            // Если дефолтное значение есть — просто берем его
                            $dependencies[] = $parameter->getDefaultValue();
                        } else {
                            // Если дефолтного значения нет, а контейнер не знает, как собрать этот простой тип,
                            // аварийно выбрасываем исключение, чтобы не уронить PHP со скрытой ошибкой
                            throw new RuntimeException(
                                "Невозможно разрешить зависимость для {$parameter->getName()} в {$controllerClass}"
                            );
                        }
                    }
                }

                // Когда все зависимости успешно собраны в массив $dependencies,
                // динамически создаем объект контроллера, передавая этот массив в его конструктор
                return $reflectionForContainer->newInstanceArgs($dependencies);
            };
        }

        return $result;
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


        // Мапим путь на дефолтный корневой неймспейс App\
        return 'App\\' . str_replace('/', '\\', $relativePart);
    }
}
