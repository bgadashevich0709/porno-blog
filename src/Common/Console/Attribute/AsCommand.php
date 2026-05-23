<?php

declare(strict_types=1);

namespace App\Common\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsCommand
{
    public function __construct(
        public string $name,
        public string $description = ''
    ) {}
}
