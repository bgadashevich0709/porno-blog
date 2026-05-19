<?php

namespace App\Common\Middleware;

use App\Common\Http\Context;

interface MiddlewareInterface
{
    /**
     * Processes an incoming request context.
     *
     * @param Context $context The current HTTP request wrapper.
     * @param callable $next The next execution layer closure in the chain.
     */
    public function handle(Context $context, callable $next): void;
}