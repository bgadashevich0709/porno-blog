<?php

declare(strict_types=1);

namespace App\Common\ServiceProvider; // Скорректируй namespace под твой корень (например, App\Common\ServiceProvider)

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ServiceProvider
{
    public function __construct() {}
}
