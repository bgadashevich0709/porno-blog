<?php

declare(strict_types=1);

namespace App\Common\Console;

use App\Common\Console\Attribute\AsCommand;
use App\Common\Container\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RegexIterator;

/**
 * Ядро консольного приложения.
 * Этот класс находит команды, парсит аргументы и запускает код на выполнение.
 */
final class Application
{
    /**
     * Здесь мы храним список всех готовых к работе команд.
     * Ключ — имя команды (например, 'cache:warm'), значение — объект самой команды.
     * @var array<string, CommandInterface>
     */
    private array $commands = [];

    /**
     * Здесь мы храним текстовые описания для каждой команды,
     * чтобы потом красиво показать их в меню помощи (help).
     * @var array<string, string>
     */
    private array $descriptions = [];

    /**
     * Принимаем DI-контейнер через конструктор,
     * чтобы автоматически создавать объекты команд со всеми их зависимостями.
     */
    public function __construct(
        private Container $container
    ) {}

    /**
     * Умный метод, который сам сканирует папки проекта и ищет классы консольных команд.
     *
     * @param string $srcPath Путь к папке, где искать файлы (например, 'src/')
     * @param string $rootNamespace Стартовый неймспейс для сборки полного имени класса
     */
    public function autoDiscoverCommands(string $srcPath, string $rootNamespace = 'App\\'): self
    {
        // Если такой папки физически нет на сервере, просто выходим
        if (!is_dir($srcPath)) {
            return $this;
        }

        // Включаем встроенные инструменты PHP для глубокого обхода папок и поиска файлов
        $directory = new RecursiveDirectoryIterator($srcPath);
        $iterator = new RecursiveIteratorIterator($directory);
        // Фильтруем файлы, чтобы оставить только те, которые заканчиваются на ".php"
        $phpFiles = new RegexIterator($iterator, '/\.php$/');

        // Бежим циклом по всем найденным PHP-файлам
        foreach ($phpFiles as $file) {
            // Получаем реальный полный путь к файлу в системе
            $absolutePath = $file->getRealPath();

            // Превращаем путь к файлу в правильное имя PHP-класса с учетом неймспейса.
            // Например, из '/var/www/src/UseCase/Console/WarmCacheCommand.php'
            // получаем строку 'App\UseCase\Console\WarmCacheCommand'
            $relativePath = str_replace([$srcPath, '.php', '/'], ['', '', '\\'], $absolutePath);
            $className = $rootNamespace . ltrim($relativePath, '\\');

            // Проверяем: если такой класс в PHP по какой-то причине не зарегистрирован — пропускаем файл
            if (!class_exists($className)) {
                continue;
            }

            // Включаем Reflection (рефлексию) — это встроенный "рентген" PHP,
            // который позволяет заглянуть внутрь класса и прочитать его структуру
            $reflection = new ReflectionClass($className);

            // Если класс абстрактный (нельзя создать объект)
            // или он НЕ реализует наш CommandInterface — он нам не подходит, пропускаем
            if ($reflection->isAbstract() || !$reflection->implementsInterface(CommandInterface::class)) {
                continue;
            }

            // Ищем над классом специальную PHP-метку — атрибут #[AsCommand]
            $attributes = $reflection->getAttributes(AsCommand::class);
            // Если такой метки над классом нет — это не наша консольная команда, пропускаем
            if (empty($attributes)) {
                continue;
            }

            // "Оживляем" атрибут-метку, превращая ее в объект, чтобы прочитать настройки
            /** @var AsCommand $attributeInstance */
            $attributeInstance = $attributes[0]->newInstance();

            // Просим наш DI-контейнер создать готовый объект этой команды.
            // Контейнер сам заглянет в конструктор команды и подкинет туда нужные сервисы и репозитории.
            /** @var CommandInterface $commandInstance */
            $commandInstance = $this->container->get($className);

            // Забираем имя команды из атрибута (например, 'cache:warm')
            $name = $attributeInstance->name;

            // Сохраняем команду и ее описание во внутренние списки класса Application
            $this->commands[$name] = $commandInstance;
            $this->descriptions[$name] = $attributeInstance->description;
        }

        // Возвращаем сам объект Application, чтобы можно было строить цепочки методов (fluent interface)
        return $this;
    }

    /**
     * Точка входа. Этот метод запускает выполнение конкретной команды.
     *
     * @param array $argv Массив сырых аргументов из терминала ($argv[0] — имя скрипта, $argv[1] — имя команды)
     */
    public function run(array $argv): int
    {
        // Пытаемся достать имя команды, которое ввел пользователь (первое слово после имени файла)
        $commandName = $argv[1] ?? null;

        // Если пользователь ничего не ввел (просто запустил `php bin/console`),
        // то показываем ему список всех доступных команд и выходим с кодом 0 (успех)
        if ($commandName === null) {
            $this->printHelp();
            return 0;
        }

        // Если пользователь ввел имя команды, которой у нас нет в списке зарегистрированных
        if (!isset($this->commands[$commandName])) {
            // Выводим красивую ошибку через хелпер, показываем меню помощи и выходим с кодом 1 (ошибка)
            ConsoleOutput::error("Ошибка: Команда '{$commandName}' не найдена.");
            $this->printHelp();
            return 1;
        }

        // Отрезаем из массива аргументов всё лишнее: имя скрипта и само имя команды.
        // Оставляем только чистые параметры (например, ['--page=5', '--force'])
        $rawArgs = array_slice($argv, 2);

        // Превращаем сырые строки параметров в удобный ассоциативный массив
        $parsedArgs = $this->parseArguments($rawArgs);

        // Достаем нужную команду из нашего списка, запускаем ее метод execute,
        // передаем туда отпарсенные аргументы и возвращаем итоговый статус (успех/ошибка) наружу в терминал
        return $this->commands[$commandName]->execute($parsedArgs);
    }

    /**
     * Метод для вывода красивого списка всех доступных команд в терминал.
     */
    private function printHelp(): void
    {
        // Выводим заголовок меню и декоративную линию разделителя
        ConsoleOutput::title("Доступные консольные команды:");
        ConsoleOutput::line(str_repeat('-', 40));

        // Если автопоиск не нашел ни одной команды в проекте
        if (empty($this->commands)) {
            ConsoleOutput::line("   Список команд пуст. Зарегистрируйте команды или запустите автопоиск.");
            return;
        }

        // Сортируем массив команд по алфавиту (по ключам) перед выводом
        ksort($this->commands);

        // Вычисляем длину самого длинного имени команды, чтобы ровно выровнять описания по колонке
        $maxLength = max(array_map('strlen', array_keys($this->commands)));

        // Бежим по всем командам и выводим их в формате: имя_команды   описание
        foreach ($this->commands as $name => $command) {
            // Считаем сколько пробелов нужно добавить для идеального выравнивания правой колонки
            $spaces = str_repeat(' ', $maxLength - strlen($name) + 4);
            // Если у команды нет описания в атрибуте — пишем стандартный текст
            $description = $this->descriptions[$name] ?: 'Нет описания';

            // Выводим строку через хелпер
            ConsoleOutput::command($name, $spaces, $description);
        }

        // Печатаем пустую строку в самом конце для красивого отступа в терминале
        ConsoleOutput::line('');
    }

    /**
     * Парсер флагов терминала. Превращает массив строк в ассоциативный массив.
     *
     * @param array $rawArgs Сырые аргументы, например: ['--page=12', '--force']
     * @return array Ассоциативный массив, например: ['page' => '12', 'force' => true]
     */
    private function parseArguments(array $rawArgs): array
    {
        $arguments = [];

        foreach ($rawArgs as $arg) {
            // Нас интересуют только те аргументы, которые начинаются с двух дефисов `--`
            if (str_starts_with($arg, '--')) {
                // Отрезаем `--` и делим строку по знаку равенства `=`, но не более чем на 2 части.
                // Строка 'page=12' превратится в массив ['page', '12']
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];

                // Если значения после знака `=` нет (например, просто `--force`),
                // то ставим по умолчанию логическое `true`
                $arguments[$key] = $parts[1] ?? true;
            }
        }

        return $arguments;
    }
}
