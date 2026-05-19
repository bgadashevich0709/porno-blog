<?php

namespace App\Common\Validator\Exception;

use Exception;

class ValidationException extends Exception
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct("Ошибка валидации входящих данных.");
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
