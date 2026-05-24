<?php

declare(strict_types=1);

namespace App\Application\Service\Meta;

interface HasMetaInterface
{
    public function getMetaTitle(): string;

    public function getMetaDescription(): string;

    public function getMetaKeywords(): string;
}
