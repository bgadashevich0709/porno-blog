<?php

namespace App\Exceptions;

class AccessDeniedException extends HttpException
{
    protected $message = 'Forbidden.';
    public function getStatusCode(): int { return 403; }
}