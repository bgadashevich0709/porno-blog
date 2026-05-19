<?php

namespace App\Application\Enum;

enum SortWay: string
{
    case asc = 'asc';
    case desc = 'desc';

    public static function labels(): array
    {
        return [
            self::desc->name => 'По убыванию (&darr;)',
            self::asc->name  => 'По возрастанию (&uarr;)',
        ];
    }
}
