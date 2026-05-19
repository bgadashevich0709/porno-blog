<?php

namespace App\Common\Validator\Constraint;

class GreaterThanOrEqualValidator implements ConstraintValidatorInterface
{
    public function validate(mixed $value, ConstraintInterface $constraint): ?string
    {
        /** @var GreaterThanOrEqual $constraint */
        $checkValue = is_string($value) ? mb_strlen($value) : $value;

        if ($checkValue < $constraint->value) {
            return str_replace('{{ limit }}', (string)$constraint->value, $constraint->message);
        }

        return null;
    }
}