<?php

declare(strict_types=1);

namespace App\Common\Config;

final class DbConfig
{
    public static function getConnectionParams(): array
    {
        return [
            'driver'   => Env::get('DB_DRIVER'),
            'host'     => Env::get('DB_HOST'),
            'user'     => Env::get('DB_USER'),
            'password' => Env::get('DB_PASSWORD'),
            'dbname'   => Env::get('DB_NAME'),
            'charset'  => Env::get('DB_CHARSET'),
        ];
    }
}
