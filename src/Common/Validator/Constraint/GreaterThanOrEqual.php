<?php

namespace App\Common\Validator\Constraint;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class GreaterThanOrEqual implements ConstraintInterface
{
    public function __construct(
        public int $value,
        public string $message = 'Значение должно быть больше или равно {{ limit }}.'
    ) {}

    public function getValidatorClass(): string
    {
        return GreaterThanOrEqualValidator::class;
    }
}