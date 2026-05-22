<?php

declare(strict_types=1);

namespace App\Common\Debug;

final class SqlProfiler
{
    private static array $slowQueries = [];
    private const float THRESHOLD_MS = 30.0; // Порог долгого запроса (30 мс)

    public static function logQuery(string $sql, array $params, float $durationMs): void
    {
        if ($durationMs >= self::THRESHOLD_MS) {
            self::$slowQueries[] = [
                'sql'      => $sql,
                'params'   => $params,
                'duration' => $durationMs,
            ];
        }
    }

    public static function getSlowQueries(): array
    {
        return self::$slowQueries;
    }
}
