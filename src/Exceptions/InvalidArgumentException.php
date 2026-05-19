<?php

namespace App\Exceptions;

class InvalidArgumentException extends HttpException
{
    protected $message = 'Invalid argument provided.';

    public function getStatusCode(): int
    {
        return 400;
    }
}
