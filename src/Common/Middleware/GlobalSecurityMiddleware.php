<?php

namespace App\Common\Middleware;

use App\Common\Http\Context;

class GlobalSecurityMiddleware implements MiddlewareInterface
{
    public function handle(Context $context, callable $next): void
    {
        $this->preventNullByteInjection();
        $this->sanitizeGlobalArrays();
        $this->setSecurityHeaders();

        $next($context);
    }

    private function preventNullByteInjection(): void
    {
        $sanitizeNull = function ($value) use (&$sanitizeNull) {
            if (is_array($value)) {
                return array_map($sanitizeNull, $value);
            }
            return is_string($value) ? str_replace(chr(0), '', $value) : $value;
        };

        $_GET = $sanitizeNull($_GET);
        $_POST = $sanitizeNull($_POST);
    }

    private function sanitizeGlobalArrays(): void
    {
        if (!empty($_POST)) { $_POST = $this->sanitizeArray($_POST); }
        if (!empty($_GET)) { $_GET = $this->sanitizeArray($_GET); }
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } else {
                $data[$key] = htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        return $data;
    }

    private function setSecurityHeaders(): void
    {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
    }

}
