<?php

declare(strict_types=1);

namespace App\Common\Config;

final class DbConfig
{
    public static function getConnectionParams(): array
    {
        return [
            'wrapperClass' => \Doctrine\DBAL\Connections\PrimaryReadReplicaConnection::class,
            'driver'       => Env::get('DB_DRIVER'),
            'primary' => [
                'host'     => Env::get('DB_HOST'),
                'user'     => Env::get('DB_USER'),
                'password' => Env::get('DB_PASSWORD'),
                'dbname'   => Env::get('DB_NAME'),
                'charset'  => Env::get('DB_CHARSET'),
            ],
            'replica' => [
                [
                    'host'     => Env::get('DB_SLAVE_HOST'),
                    'user'     => Env::get('DB_USER'),
                    'password' => Env::get('DB_PASSWORD'),
                    'dbname'   => Env::get('DB_NAME'),
                    'charset'  => Env::get('DB_CHARSET'),
                ],
            ],
        ];
    }
}
