<?php

namespace App\Common\Middleware;

use App\Common\Http\Context;

class LoggerMiddleware implements MiddlewareInterface
{
    public function handle(Context $context, callable $next): void
    {
        error_log("[HTTP LOG] Processing request: {$context->method} {$context->uri}");

        $next($context);
    }
}
