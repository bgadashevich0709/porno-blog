<?php

declare(strict_types=1);

namespace App\Common\Middleware;

use App\Common\Config\Env;
use App\Common\Debug\CacheProfiler;
use App\Common\Debug\SqlProfiler;
use App\Common\Http\Context;

readonly class ProfilerMiddleware implements MiddlewareInterface
{
    public function handle(Context $context, callable $next): void
    {
        $isDebug = filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

        if (!$isDebug) {
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
        $isCacheHit = CacheProfiler::isHit();

        include __DIR__ . '/../Debug/view/profiler.view.php';
    }
}
