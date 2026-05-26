<?php

namespace App\Common\Exception;

use App\Exceptions\HttpException;

class UnauthorizedException extends HttpException
{
    protected $message = 'Unauthorized.';

    public function getStatusCode(): int
    {
        return 401;
    }
}
