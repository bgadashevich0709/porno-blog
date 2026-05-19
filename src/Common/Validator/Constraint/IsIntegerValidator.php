<?php

namespace App\Common\Validator\Constraint;

class IsIntegerValidator implements ConstraintValidatorInterface
{
    public function validate(mixed $value, ConstraintInterface $constraint): ?string
    {
        /** @var IsInteger $constraint */
        if (!is_int($value)) {
            return $constraint->message;
        }

        return null;
    }
}
