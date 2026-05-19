<?php

namespace App\Exceptions;

use Exception;

abstract class HttpException extends Exception
{
    abstract public function getStatusCode(): int;
}