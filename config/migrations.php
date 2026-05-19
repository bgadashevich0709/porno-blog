<?php

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
    'migrations_paths' => [
        'DoctrineMigrations' => '/var/www/migrations',
    ],
    'all_or_nothing' => true,
    'check_database_platform' => true,
];
