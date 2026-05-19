<?php

namespace App\Common\Validator\Constraint;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class LessThanOrEqual implements ConstraintInterface
{
    public function __construct(
        public int $value,
        public string $message = 'Значение должно быть меньше или равно {{ limit }}.'
    ) {}

    public function getValidatorClass(): string
    {
        return LessThanOrEqualValidator::class;
    }
}