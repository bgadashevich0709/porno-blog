<?php

declare(strict_types=1);

namespace App\Common\Console;

interface CommandInterface
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    /**
     * @param array<string, string|bool> $arguments
     * @return int Статус ответа (0 - успех)
     */
    public function execute(array $arguments): int;
}
