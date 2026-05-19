<?php

namespace App\Common\Validator\Constraint;

interface ConstraintValidatorInterface
{
    /**
     * @param mixed $value Валидируемое значение из свойства DTO
     * @param ConstraintInterface $constraint Экземпляр атрибута-конфига
     * @return string|null Возвращает текст ошибки или null, если значение валидно
     */
    public function validate(mixed $value, ConstraintInterface $constraint): ?string;
}