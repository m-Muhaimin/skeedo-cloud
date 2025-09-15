<?php

declare(strict_types=1);
return [
    'table_storage' => [
        'table_name' => 'migration',
        'version_column_name' => 'version',
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'Migrations\MySql' => './migrations/mysql',
        'Migrations\Sqlite' => './migrations/sqlite',
    ]
];
