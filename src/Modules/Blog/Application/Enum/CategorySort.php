<?php

namespace App\Modules\Blog\Application\Enum;

enum CategorySort: string
{
    case views = 'views';
    case createdAt  = 'createdAt';


    public static function labels(): array
    {
        return [
            self::views->name => 'Количеству просмотров',
            self::createdAt ->name  => 'Дате публикации',
        ];
    }
}
