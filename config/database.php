<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ],
        'mongodb' => [
            'driver'   => 'mongodb',
            'host'     => env('MONGODB_HOST', 'localhost'),
            'port'     => env('MONGODB_PORT', 27017),
            'database' => env('MONGODB_DATABASE'),
            'username' => env('MONGODB_USERNAME'),
            'password' => env('MONGODB_PASSWORD'),
            'options'  => [
                'database' => 'admin'
            ]
        ],
        'mongodb_mw' => [
            'driver'   => 'mongodb',
            'host'     => env('MONGODB_MW_HOST', 'localhost'),
            'port'     => env('MONGODB_MW_PORT', 27017),
            'database' => env('MONGODB_MW_DATABASE'),
            'username' => env('MONGODB_MW_USERNAME'),
            'password' => env('MONGODB_MW_PASSWORD'),
            'options'  => [
                'database' => 'admin'
            ]
        ]
    ]
];