<?php

declare(strict_types=1);

namespace App\Common\Console;

final class ConsoleOutput
{
    public static function line(string $message): void
    {
        echo $message . "\n";
    }

    public static function title(string $message): void
    {
        echo "💻 " . $message . "\n";
    }

    public static function error(string $message): void
    {
        echo "❌ \033[31m" . $message . "\033[0m\n\n";
    }

    public static function command(string $name, string $spaces, string $description): void
    {
        echo "  \033[32m{$name}\033[0m{$spaces}{$description}\n";
    }
}
