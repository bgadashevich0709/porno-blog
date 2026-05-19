<?php

namespace App\Common\Validator\Constraint;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class IsInteger implements ConstraintInterface
{
    public function __construct(
        public string $message = "Значение должно быть целым числом"
    ) {}

    public function getValidatorClass(): string
    {
        return IsIntegerValidator::class;
    }
}
