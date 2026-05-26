<?php

declare(strict_types=1);

namespace App\Common\Middleware;

use App\Common\Exception\UnauthorizedException;
use App\Common\Http\Context;
use App\Common\Security\JwtService;
use Closure;

class AuthMiddleware
{
    public function __construct(private JwtService $jwtService) {}

    /**
     * @throws UnauthorizedException
     */
    public function handle(Context $context, Closure $next)
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        $token = null;

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        if (!$token && isset($_COOKIE['ACCESS_TOKEN'])) {
            $token = $_COOKIE['ACCESS_TOKEN'];
        }

        if (!$token) {
            throw new UnauthorizedException('Пошел нахуй, непрошенный гость. Авторизуйся сначала.');
        }

        $userData = $this->jwtService->validateToken($token);

        if (!$userData) {
            throw new UnauthorizedException('Твой токен — залупа, либо он уже сдох. Авторизуйся нормально.');
        }

        $_REQUEST['user'] = $userData;

        return $next($context);
    }
}
