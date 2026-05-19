<?php

namespace App\Common\Middleware;

use App\Common\Http\Context;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Context $context, callable $next): void
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $_GET['token'] ?? null;

        if ($token !== 'secret_token_123') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid or missing token.']);
            return; // Breaking the chain loop instantly! Controller will not execute.
        }

        $context->attributes['userId'] = 42;

        $next($context);
    }
}
