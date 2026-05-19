<?php

namespace App\Common\Validator\Constraint;

interface ConstraintInterface
{
    public function getValidatorClass(): string;
}
