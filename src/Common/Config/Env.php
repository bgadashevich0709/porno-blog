<?php

declare(strict_types=1);

namespace App\Common\Config;

final class Env
{
    private static bool $isLoaded = false;

    /**
     * Получает значение из окружения по ключу с поддержкой автоматической загрузки .env
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$isLoaded) {
            self::bootstrap();
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }

    /**
     * Простой и быстрый самописный загрузчик .env файла
     */
    private static function bootstrap(): void
    {
        self::$isLoaded = true;

        $envPath = dirname(__DIR__, 3) . '/.env';

        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                $value = preg_replace('/^["\']|["\']$/', '', $value);

                if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}
