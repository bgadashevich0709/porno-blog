<?php

declare(strict_types=1);

namespace App\Common\Http;

use App\Common\Container\Container;
use App\Common\Validator\Exception\ValidationException;
use App\Common\Validator\Validator;
use ReflectionClass;
use ReflectionNamedType;

readonly class RequestDtoResolver
{
    public function __construct(
        private Container $container
    ) {}

    /**
     * Превращает данные HTTP-запроса в валидированный объект DTO.
     *
     * @param string $dtoClass Имя класса DTO, который нужно создать (например, UserFilterDto::class).
     * @param Request $request Объект текущего HTTP-запроса, откуда берутся данные.
     * @return object Возвращает полностью заполненный и проверенный объект DTO.
     *
     * @throws \ReflectionException Если передан несуществующий класс.
     * @throws ValidationException Если данные из запроса не прошли проверку валидатора.
     */
    public function resolve(string $dtoClass, Request $request): object
    {
        // 1. Включаем "рефлексию" (зеркало), чтобы изучить структуру класса DTO изнутри
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        // Если у DTO нет конструктора (нет параметров), просто создаем пустой объект
        if ($constructor === null) {
            return new $dtoClass();
        }

        // 2. Берем массив данных из GET-параметров URL (например, ['age' => '25', 'status' => 'active'])
        $data = $request->query;
        $args = []; // Здесь будем копить готовые аргументы для передачи в конструктор

        // 3. Цикл: перебираем каждый параметр, который ожидает конструктор DTO-класса
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName(); // Имя переменной (например, "age" или "status")
            $type = $parameter->getType(); // Тип переменной (например, int, string, или Enum)

            // Проверяем, пришел ли такой параметр в URL-запросе
            $hasParam = array_key_exists($name, $data);
            // Если пришел — берем его, если нет — временно ставим null
            $rawValue = $hasParam ? $data[$name] : null;

            // Если у параметра в коде четко указан тип (например: string, int, UserStatus)
            if ($type instanceof ReflectionNamedType) {
                $typeName = $type->getName(); // Получаем текстовое имя типа

                // СЛУЧАЙ А: Тип параметра — это BackedEnum (перечисление с фиксированными значениями)
                if (is_subclass_of($typeName, \BackedEnum::class)) {
                    // Если параметр передан и он не пустой
                    if ($hasParam && $rawValue !== '') {
                        // Пытаемся превратить строку/число из URL в валидный элемент Enum
                        $enumValue = $typeName::tryFrom((string) $rawValue);

                        // Если получилось — берем его. Если нет (пришел мусор) — берем дефолтное значение из PHP-кода (если оно есть)
                        $rawValue = $enumValue ?? (
                            $parameter->isDefaultValueAvailable()
                            ? $parameter->getDefaultValue()
                            : null
                        );
                    } else {
                        // Если параметр в URL не передали, используем дефолтное значение из конструктора (если задано)
                        $rawValue = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                    }
                }

                // СЛУЧАЙ Б: Если пришло непустое значение обычного типа (скалярного)
                elseif ($rawValue !== null) {
                    // Принудительно превращаем строку из URL в жесткий integer, если DTO ждет число
                    if ($typeName === 'int') {
                        $rawValue = (int) $rawValue;
                        // Принудительно превращаем в string, если DTO ждет строку
                    } elseif ($typeName === 'string') {
                        $rawValue = (string) $rawValue;
                    }
                }
                // СЛУЧАЙ В: Значение из URL не пришло вовсе
                else {
                    // Проверяем, есть ли у свойства дефолтное значение (например: $status = 'active') и берем его
                    $rawValue = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                }
            }

            // Сохраняем обработанное и приведенное к нужному типу значение в массив аргументов
            $args[$name] = $rawValue;
        }

        // 4. Создаем экземпляр DTO класса, передавая в его конструктор собранный массив аргументов
        $dtoInstance = $reflection->newInstanceArgs($args);

        // 5. Передаем созданный объект в валидатор для проверки бизнес-правил (например, #[Assert\NotBlank])
        $validator = new Validator($this->container);
        $validator->validate($dtoInstance); // Если найдет ошибки, тут сработает ValidationException

        // 6. Возвращаем полностью готовый, чистый и проверенный объект
        return $dtoInstance;
    }
}
