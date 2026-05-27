<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Console;

use App\Common\Console\Attribute\AsCommand;
use App\Common\Console\CommandInterface;
use App\Common\Console\ConsoleOutput;

#[AsCommand(name: 'app:hello', description: 'Простая команда для проверки консольного движка')]
final readonly class HelloWorldCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        ConsoleOutput::line("👋 Привет, мир! Консольный движок нашего фреймворка работает идеально!");

        return CommandInterface::SUCCESS;
    }
}
