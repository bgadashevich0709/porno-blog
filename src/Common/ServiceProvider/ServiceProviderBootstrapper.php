<?php

declare(strict_types=1);

namespace App\Common\ServiceProvider;

use App\Common\Container\Container;
use App\Common\ServiceProvider\ServiceProvider as ServiceProviderAttribute;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RegexIterator;

/**
 * Класс-компонент для автоматического обнаружения и инициализации сервис-провайдеров.
 *
 * Сканирует заданные директории проекта, находит классы, помеченные атрибутом
 * #[ServiceProvider], проверяет их на соответствие интерфейсу и выполняет
 * их регистрацию в DI-контейнере. Поддерживает кэширование структуры для Production-окружения.
 */
final class ServiceProviderBootstrapper
{
    /**
     * @var string Абсолютный путь к файлу, в который будет сохранен кэш списка провайдеров.
     */
    private string $cacheFile;

    /**
     * Конструктор бутстраппера.
     *
     * @param array<string> $scanDirs Список абсолютных путей к директориям, которые необходимо сканировать.
     * @param string $cacheDir Абсолютный путь к директории для хранения файлов кэша приложения.
     */
    public function __construct(
        private readonly array $scanDirs,
        string $cacheDir
    ) {
        // Формируем путь к файлу кэша провайдеров в указанной системной директории кэша
        $this->cacheFile = $cacheDir . '/service_providers_cache.php';
    }

    /**
     * Основная точка входа. Запускает процесс сборки и регистрирует провайдеры в контейнере.
     *
     * @param Container $container Экземпляр используемого DI-контейнера приложения.
     * @param bool $isDebug Флаг режима отладки. Если true — кэш игнорируется, файлы сканируются на каждом запросе.
     * @return void
     *
     * @throws \ReflectionException Если не удается проанализировать найденные классы.
     */
    public function boot(Container $container, bool $isDebug = true): void
    {
        // 1. Получаем массив полных имен (FQCN) классов провайдеров
        $providerClasses = $this->getProviderClasses($isDebug);

        // 2. Итерируемся по списку классов, создаем экземпляры и вызываем их регистрацию
        foreach ($providerClasses as $className) {
            /** @var ServiceProviderInterface $provider */
            $provider = new $className();

            // Внедряем зависимости модуля/компонента в контейнер приложения
            $provider->register($container);
        }
    }

    /**
     * Возвращает оптимизированный массив имен классов провайдеров.
     * Извлекает данные из готового кэш-файла или запускает физическое сканирование диска.
     *
     * @param bool $isDebug Режим разработки.
     * @return array<string> Массив строк с полными именами классов (например, ['App\Modules\Blog\Provider\BlogServiceProvider'])
     */
    private function getProviderClasses(bool $isDebug): array
    {
        // Если это Production (не дебаг) и файл кэша уже существует — мгновенно отдаем его
        if (!$isDebug && file_exists($this->cacheFile)) {
            /** @var array<string> */
            return require $this->cacheFile;
        }

        $classes = [];

        // Перебираем все зарегистрированные директории для поиска (Common, Modules и т.д.)
        foreach ($this->scanDirs as $dir) {
            if (is_dir($dir)) {
                // Сканируем каждую директорию и объединяем результаты в общий пул
                $classes = array_merge($classes, $this->scanDirectory($dir));
            }
        }

        // Очищаем массив от возможных дубликатов (если пути сканирования случайно пересеклись)
        $classes = array_unique($classes);

        // Если мы в режиме Production, сохраняем собранную структуру в кэш-файл для последующих запросов
        if (!$isDebug) {
            $cacheDir = dirname($this->cacheFile);

            // Проверяем наличие папки var/cache, если её нет — создаем рекурсивно
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0o777, true);
            }

            // Генерируем валидный PHP-код возврата массива для высокой скорости работы через OPcache
            $content = '<?php return ' . var_export($classes, true) . ';';
            file_put_contents($this->cacheFile, $content);
        }

        return $classes;
    }

    /**
     * Рекурсивно сканирует конкретную директорию на диске в поиске PHP-файлов провайдеров.
     *
     * @param string $dir Абсолютный путь к целевой директории.
     * @return array<string> Список найденных валидных классов провайдеров в этой директории.
     *
     * @throws \ReflectionException
     */
    private function scanDirectory(string $dir): array
    {
        $classes = [];

        // Создаем рекурсивный итератор для обхода дерева вложенных папок
        $directory = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($directory);

        // Фильтруем поток файлов, оставляя строго файлы с расширением .php (регистронезависимо)
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        /** @var array<string> $file */
        foreach ($regex as $file) {
            $filePath = $file[0];

            // Пытаемся распарсить полное имя класса из структуры файла
            $className = $this->getClassNameFromFile($filePath);

            // Проверяем корректность полученного имени и факт загрузки класса в рантайм (PSR-4 автолоад)
            if (!$className || !class_exists($className)) {
                continue;
            }

            // Используем стандартную Reflection API для глубокого анализа структуры класса
            $reflection = new ReflectionClass($className);

            // Ищем наш кастомный маркерный атрибут #[ServiceProvider] над анализируемым классом
            $attributes = $reflection->getAttributes(ServiceProviderAttribute::class);

            // Исключаем класс, если атрибут отсутствует или если класс является абстрактным/интерфейсом
            if (empty($attributes) || $reflection->isAbstract()) {
                continue;
            }

            // Проверяем, реализует ли класс обязательный контракт ServiceProviderInterface
            if ($reflection->implementsInterface(ServiceProviderInterface::class)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Быстрый парсер содержимого PHP-файла без полной его интерпретации.
     * Извлекает декларацию namespace и имя класса с помощью регулярных выражений.
     *
     * @param string $filePath Абсолютный путь к физическому файлу на диске.
     * @return string|null Полное имя класса (FQCN) или null, если структура не распознана.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        // Читаем исходный код файла
        $content = file_get_contents($filePath);

        // Поиск пространства имен: ищем паттерн "namespace ИмяПространства;"
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        // Поиск имени класса: ищем паттерн "class ИмяКласса"
        if (!preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        // Собираем полное имя класса, убирая случайные пробелы: \Namespace\Subspace\ClassName
        return trim($namespaceMatches[1]) . '\\' . trim($classMatches[1]);
    }
}
