<?php

namespace App\Common\Http;

class Context
{
    public function __construct(
        public readonly string $uri,
        public readonly string $method,
        public array $attributes = []
    ) {}
}