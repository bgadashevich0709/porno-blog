<?php

namespace App\Modules\Blog\Application\Enum;

enum ImageFormat: string
{
    case List = 'list';
    case Page = 'page';

    public function getDefaultPlaceholder(): string
    {
        return match ($this) {
            self::List => '/images/placeholders/blog-list-default.png',
            self::Page => '/images/placeholders/blog-page-default.png',
        };
    }
}
