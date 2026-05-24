<?php

declare(strict_types=1);

namespace App\Common\Router\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsController {}
