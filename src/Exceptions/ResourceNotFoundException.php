<?php

namespace App\Exceptions;

class ResourceNotFoundException extends HttpException
{
    protected $message = 'Resource not found.';
    public function getStatusCode(): int { return 404; }
}