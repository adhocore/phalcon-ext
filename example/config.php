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
    'cors' => [
        'exposedHeaders' => [],
        // Should be in lowercases.
        'allowedHeaders' => ['x-requested-with', 'content-type', 'authorization'],
        // Should be in uppercase.
        'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        // Requests originating from here can entertain CORS.
        'allowedOrigins' => [
            'http://127.0.0.1:1234',
        ],
        // Cache preflight for 7 days (expressed in seconds).
        'maxAge'         => 604800,
    ],
    'throttle' => [
        'maxHits' => [
            // Mintues => Max Hits
            1    => 5,
            60   => 250,
            1440 => 4500,
        ],
        'checkUserAgent' => false,
        'prefix'         => '_',
    ],
    'httpCache' => [
        // cache life- time to live
        'ttl'       => 60, // 60 minutes
        'routes'    => [
            // for absolute uri, prepend forward `/`
            '/di',
            '/logger',
            // or you can use route name without a `/`
            'home',
        ],
    ],
];
