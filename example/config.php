<?php

return [
    'database' => [
        'driver' => 'sqlite',
        'dbname' => __DIR__ . '/.var/db.db',
        // ... other options (see phalcon &/or pdo docs)
    ],
    'sqllogger' => [
        'enabled'        => true,
        'logPath'        => __DIR__ . '/.var/sql/', // directory
        'addHeader'      => true,
        'backtraceLevel' => 5,
        'skipFirst'      => 2, // skip create/delete
    ],
    'mail' => [
        'driver' => 'null',
        'from'   => [
            'name' => 'Test',
            'email' => 'test@localhost',
        ],
        'logger' => [
            'enabled' => true,
            'logPath' => __DIR__ . '/.var/mail/', // directory
            'type'    => 'eml', // options: json, html, eml
        ],
    ],
    'view' => [
        'dir' => __DIR__ . '/view/',
    ],
    'twig' => [
        'view_dirs'   => [__DIR__ . '/view/'], // array
        'auto_reload' => getenv('APP_ENV') !== 'prod',
        'cache'       => __DIR__ . '/.var/view/',
        // ... other options (see twig docs)
    ],
];
