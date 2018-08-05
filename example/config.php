<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

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
        'skipFirst'      => 1, // skip delete
    ],
    'mail' => [
        'driver' => 'null',
        'from'   => [
            'name'  => 'Test',
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
            1    => 10,
            60   => 250,
            1440 => 4500,
        ],
        'checkUserAgent' => false,
        // Cache key prefix
        'prefix'         => '_',
    ],
    'httpCache' => [
        // cache life- time to live
        'ttl'       => 60, // 60 minutes
        'routes'    => [
            // for absolute uri, prepend forward `/`
            '/di',
            '/logger',
            '/mail',
            '/db',
            // or you can use route name without a `/`
            'home',
        ],
    ],
    'apiAuth' => [
        // 14 days in seconds (http://stackoverflow.com/questions/15564486/why-do-refresh-tokens-expire-after-14-days)
        'refreshMaxAge'  => 1209600,
        // Prefix to use in stored tokens (max 4 chars)
        'tokenPrefix'    => 'RF/',
        // The route to generate/refresh access tokens.
        // genrerate: curl -XPOST -d 'grant_type=password&username=&password=' /api/auth
        // refresh:   curl -XPOST -d 'grant_type=refresh_token&refresh_token=' /api/auth
        // It can also accept json payload:
        //   -H 'content-type: application/json' -d {"grant_type":"refresh_token","refresh_token":""}
        'authUri' => '/api/auth',

        // The permission scopes required for a route
        'scopes' => [
            '/some/uri' => 'admin',
            '/next/uri' => 'user',
        ],

        // Json Web tokens configuration.
        'jwt'            => [
            'keys'       => [
                // kid => key (first one is default always)
                'default' => '*((**@$#@@KJJNN!!#D^G&(U)KOIHIYGTFD',
            ],
            'algo'       => 'HS256',
            // 15 minutes in seconds.
            'maxAge'     => 900,
            // Grace time in seconds.
            'leeway'     => 10,
            'passphrase' => '',
            // Name of the app/project
            'issuer'     => 'phalcon-ext',
        ],
    ],
];
