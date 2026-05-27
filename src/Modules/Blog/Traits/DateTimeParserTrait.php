<?php

namespace App\Modules\Blog\Traits;

use DateTimeImmutable;
use Exception;
use RuntimeException;

trait DateTimeParserTrait
{
    protected static function parseRequiredDateTime(array $data, string $key): DateTimeImmutable
    {
        if (!isset($data[$key])) {
            throw new RuntimeException("Ключ '{$key}' отсутствует в переданных данных.");
        }

        try {
            return new DateTimeImmutable($data[$key]);
        } catch (Exception $e) {
            throw new RuntimeException(
                "Не удалось создать объект даты из значения '{$data[$key]}'. Ошибка: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
