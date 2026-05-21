<?php

declare(strict_types=1);

namespace App\Common\Middleware;

use App\Common\Config\Env;
use App\Common\Debug\SqlProfiler;
use App\Common\Http\Context;

readonly class ProfilerMiddleware implements MiddlewareInterface
{
    public function handle(Context $context, callable $next): void
    {
        $environment = Env::get('APP_ENV', 'dev');

        if ($environment === 'production' || $environment === 'prod') {
            $next($context);
            return;
        }

        $startTime = microtime(true);

        $next($context);

        $totalTimeMs = (microtime(true) - $startTime) * 1000;

        $this->renderPanel($totalTimeMs);
    }

    private function renderPanel(float $totalTimeMs): void
    {
        $slowQueries = SqlProfiler::getSlowQueries();
        $hasSlowQueries = !empty($slowQueries);

        $isCacheHit = !$hasSlowQueries;

        echo "<div style='background: #141416; color: #FFF; font-family: monospace; padding: 20px; border-top: 4px solid #00F0FF; position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999; box-shadow: 0px -5px 25px rgba(0,0,0,0.7); font-size: 13px;'>";

        echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: " . ($hasSlowQueries ? '15px' : '0') . ";'>";
        echo "<div>";
        echo "<span style='font-size: 16px; font-weight: bold; color: #00F0FF; margin-right: 20px;'>🛠️ GLOBAL PAGE PROFILER</span>";

        $cacheColor = $isCacheHit ? '#55FF55' : '#FF5555';
        $cacheStatus = $isCacheHit ? 'ИСПОЛЬЗОВАЛСЯ (HIT)' : 'НЕ ИСПОЛЬЗОВАЛСЯ (MISS)';
        echo "КЭШ: <span style='color: {$cacheColor}; font-weight: bold;'>{$cacheStatus}</span>";
        echo "</div>";

        $timeColor = $totalTimeMs > 40 ? '#FF5555' : '#55FF55';
        echo "<div style='font-size: 15px;'>";
        echo "🚀 <b>ВРЕМЯ СТРАНИЦЫ:</b> <span style='color: {$timeColor}; font-weight: bold;'>" . number_format($totalTimeMs, 2) . " ms</span>";
        echo "</div>";
        echo "</div>";

        if ($hasSlowQueries) {
            echo "<div style='background: #1C1C1E; padding: 15px; border-radius: 4px; border-left: 4px solid #FF5555; max-height: 150px; overflow-y: auto;'>";
            echo "<b style='color: #FF5555; font-size: 14px;'>⚠️ ОБНАРУЖЕНЫ МЕДЛЕННЫЕ ЗАПРОСЫ (> 50 ms):</b>";
            echo "<ul style='list-style: none; padding-left: 0; margin-top: 10px; margin-bottom: 0;'>";

            foreach ($slowQueries as $query) {
                echo "<li style='margin-bottom: 12px; border-bottom: 1px solid #2C2C2E; padding-bottom: 8px;'>";
                echo "⏱️ <span style='color: #FF5555; font-weight: bold;'>" . number_format($query['duration'], 2) . " ms</span> | ";
                echo "<code style='color: #E5E5EA;'>" . htmlspecialchars($query['sql']) . "</code>";
                if (!empty($query['params'])) {
                    echo "<br><small style='color: #8E8E93;'>Параметры: " . json_encode($query['params'], JSON_UNESCAPED_UNICODE) . "</small>";
                }
                echo "</li>";
            }

            echo "</ul>";
            echo "</div>";
        }
        echo "</div>";
    }
}
