<?php

namespace App\Common\Response\Startegy;

interface ResponseStrategyInterface
{
    public function render(string $target, array $data): void;
}
