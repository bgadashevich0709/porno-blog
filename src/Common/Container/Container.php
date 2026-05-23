<?php

namespace App\Common\Container;

use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Наш самописный DI-Контейнер. 
 * Он автоматически создает объекты классов и сам подставляет 
 * им нужные зависимости в конструктор, чтобы нам не делать это вручную.
 */
class Container implements ContainerInterface
{
    // Здесь мы храним "рецепты" создания объектов (инструкции, строки, замыкания)
    private array $definitions = [];
    
    // А здесь хранятся уже готовые, один раз созданные объекты (Одиночки / Singletons)
    private array $instances = [];

    /**
     * Метод для ручной регистрации сервиса или рецепта.
     * Запоминаем, как создавать штуку по её ID (обычно это имя класса или интерфейса).
     */
    public function set(string $id, mixed $definition): void
    {
        $this->definitions[$id] = $definition;
        // Если старая копия объекта уже была создана, выкидываем её, чтобы при запросе создался свежак
        unset($this->instances[$id]);
    }

    /**
     * Контекстное связывание.
     * Нужно, когда конкретному классу ($targetClass) в конкретный аргумент ($parameterName)
     * нужно передать специфическую настройку или строку, а не просто объект.
     */
    public function setContextual(string $targetClass, string $parameterName, mixed $definition): void
    {
        // Создаем уникальный текстовый ключ для этой настройки
        $contextualKey = "context:{$targetClass}:{$parameterName}";
        $this->definitions[$contextualKey] = $definition;
    }

    /**
     * Самый главный метод. Он выдает готовый объект по его ID.
     * @throws Exception
     */
    public function get(string $id): mixed
    {
        // ЭТАП 1: Проверяем, может мы этот объект уже создавали раньше?
        // Если да — просто отдаем его из памяти, экономя ресурсы.
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // ЭТАП 2: Если для этого ID у нас есть ручной "рецепт" (definition)
        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            // Если рецепт — это функция (Closure), запускаем её, передав внутрь сам контейнер
            if ($definition instanceof \Closure) {
                return $this->instances[$id] = $definition($this);
            }

            // Если рецепт — это просто строка с именем существующего класса, 
            // отправляем этот класс на автоматическую сборку зависимостей (autowire)
            if (is_string($definition) && class_exists($definition)) {
                return $this->instances[$id] = $this->autowire($definition);
            }

            // Если это просто готовое значение (например, строка конфигурации), отдаем как есть
            return $this->instances[$id] = $definition;
        }

        // ЭТАП 3: Если рецепта нет, но переданный ID — это обычный существующий класс.
        // Магия автосборки: пытаемся собрать его на лету без всяких настроек.
        if (class_exists($id)) {
            return $this->instances[$id] = $this->autowire($id);
        }

        // ЭТАП 4: Что делать, если нас попросили создать ИНТЕРФЕЙС?
        // Напрямую интерфейс создать нельзя, нужно искать класс, который его реализует.
        if (interface_exists($id)) {
            // Шаг А: Бежим по вообще ВСЕМ зарегистрированным ручным рецептам.
            // Если ключ рецепта — существующий класс, и он реализует этот интерфейс — создаем его.
            foreach ($this->definitions as $definedId => $definition) {
                if (class_exists($definedId)) {
                    $ref = new ReflectionClass($definedId);
                    if ($ref->implementsInterface($id)) {
                        return $this->instances[$id] = $this->get($definedId);
                    }
                }
            }

            // Шаг Б: Умный фоллбек по имени.
            // Убираем слово 'Interface' из названия. Например: UserRepositoryInterface -> UserRepository.
            // Если такой класс реально существует в проекте и реализует этот интерфейс — собираем его!
            $withoutInterface = str_replace('Interface', '', $id);
            if (class_exists($withoutInterface)) {
                $ref = new ReflectionClass($withoutInterface);
                if ($ref->implementsInterface($id)) {
                    return $this->instances[$id] = $this->autowire($withoutInterface);
                }
            }

            // Шаг В: Сканирование объявленных классов.
            // Пытаемся найти реализацию интерфейса в том же пространстве имен (namespace).
            $pathParts = explode('\\', $id);
            $namespace = implode('\\', array_slice($pathParts, 0, -1));

            // Берем вообще все классы, о которых PHP сейчас знает в памяти
            $declaredClasses = get_declared_classes();
            $implementations = [];

            foreach ($declaredClasses as $className) {
                // Если класс не из нашего нэймспейса — пропускаем
                if (!str_starts_with($className, $namespace)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);
                // Если класс можно создать (он не абстрактный) и он реализует наш интерфейс — запоминаем
                if ($reflection->isInstantiable() && $reflection->implementsInterface($id)) {
                    $implementations[] = $className;
                }
            }

            // Если среди загруженных классов нашли ровно один подходящий — автоматически собираем его!
            if (count($implementations) === 1) {
                return $this->instances[$id] = $this->autowire($implementations[0]);
            }

            // Если нашли больше одного класса — контейнер в панике, он не знает какой выбрать.
            // Выкидываем ошибку и просим разработчика настроить это вручную через setContextual
            if (count($implementations) > 1) {
                throw new Exception(
                    "Interface '{$id}' has multiple implementations: [" . implode(', ', $implementations) . "]. " .
                    "Please configure contextual binding explicitly using setContextual()."
                );
            }
        }

        // Если дошли сюда и ничего не нашли — всё плохо, кидаем ошибку.
        throw new Exception("Service, Interface or Class '{$id}' cannot be resolved.");
    }

    /**
     * Проверка: умеет ли вообще наш контейнер отдавать такую штуку?
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || isset($this->instances[$id])
            || class_exists($id)
            || interface_exists($id);
    }

    /**
     * Внутренний механизм "автосборки" (через Рефлексию).
     * Сканирует конструктор класса, определяет, какие ему нужны зависимости, 
     * автоматически создает их через get() и собирает итоговый объект.
     * 
     * @throws \ReflectionException
     * @throws Exception
     */
    private function autowire(string $class): mixed
    {
        // Заглядываем внутрь структуры класса через встроенное PHP-зеркало (Reflection)
        $reflection = new ReflectionClass($class);

        // Если это абстрактный класс или интерфейс — его нельзя создать физически, ругаемся
        if (!$reflection->isInstantiable()) {
            throw new Exception("Class '{$class}' is not instantiable.");
        }

        // Вытаскиваем конструктор класса
        $constructor = $reflection->getConstructor();

        // Если конструктора вообще нет (аргументы не нужны) — просто создаем объект «голышом»
        if ($constructor === null) {
            return new $class();
        }

        // Если конструктор есть, вытаскиваем список его параметров (аргументов)
        $parameters = $constructor->getParameters();
        $dependencies = []; // Сюда будем складывать готовые аргументы для передачи в конструктор

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            // Проверяем: может для этого параметра есть индивидуальная контекстная настройка?
            $contextualKey = "context:{$class}:{$paramName}";

            if (isset($this->definitions[$contextualKey])) {
                $definition = $this->definitions[$contextualKey];
                // Если настройка — функция, запускаем её, иначе берем готовое значение
                $dependencies[] = $definition instanceof \Closure ? $definition($this) : $definition;
                continue; // С этим параметром разобрались, идем к следующему
            }

            // Если контекстной настройки нет, смотрим на тип аргумента (тайп-хинт)
            $type = $parameter->getType();

            // Если тип аргумента вообще не указан (просто $variable) — контейнер бессилен, падает с ошибкой
            if ($type === null) {
                throw new Exception("Cannot resolve parameter '{$paramName}' in '{$class}': Missing type-hint.");
            }

            // Если тип встроенный (строка, инт, массив, булево) — автосборка не сработает, нужна была настройка
            if ($type->isBuiltin()) {
                throw new Exception("Cannot autowire built-in parameter '{$paramName}' in '{$class}'.");
            }

            // Если тип — это другой класс или интерфейс, рекурсивно запрашиваем его у нашего же контейнера через get()
            // Защита типов для старых версий PHP
            $dependencies[] = $this->get($type instanceof \ReflectionNamedType ? $type->getName() : (string) $type);
        }

        // Когда все зависимости из конструктора успешно созданы и лежат в массиве, 
        // создаем объект нашего класса, передавая туда этот массив аргументов.
        return $reflection->newInstanceArgs($dependencies);
    }
}
