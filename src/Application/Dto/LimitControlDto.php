<?php

namespace App\Application\Dto;

class LimitControlDto
{
    public function __construct(
        public array $options,
        public int   $current
    ) {}
}