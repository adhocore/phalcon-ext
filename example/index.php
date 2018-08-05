<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View\Simple as SimpleView;
use PhalconExt\Example\MicroController;
use PhalconExt\Http\Middleware\ApiAuth;
use PhalconExt\Http\Middleware\Cache;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Http\Middleware\Throttle;
use PhalconExt\Http\Middlewares;

// Micro app

// In micro mode, most of the di services are the same
$di = require __DIR__ . '/bootstrap.php';

// However we will use simple view here
$di->setShared('view', function () {
    $view = new SimpleView;

    $view->setViewsDir($this->get('config')->toArray()['view']['dir']);
    $view->registerEngines([
        '.twig' => 'twig',
    ]);

    return $view;
});

$app = new MicroApplication($di);

$app->getRouter()->setUriSource(Router::URI_SOURCE_GET_URL);

$app->mount((new Collection)
    ->setPrefix('/')
    ->setHandler(MicroController::class, true)
    ->get('/', 'indexAction', 'home')
    ->get('db', 'dbAction')
    ->get('di', 'diAction')
    ->get('mail', 'mailAction')
    ->get('logger', 'loggerAction')
    ->get('validation', 'validationAction')
    ->get('cors', 'corsAction')
    // Need to allow OPTIONS request for cors enabled endpoint!
    // (But not always, simple requests can do without it.)
    ->options('corsheader', 'corsHeaderAction')
    ->get('corsheader', 'corsHeaderAction')
    ->post('api/auth', 'authAction')
);

$app->notFound(function () use ($di) {
    return $di->get('response')->setContent('404 Not Found')->setStatusCode(404);
});

// For test return the app instance
if (getenv('APP_ENV') === 'test') {
    return $app;
}

(new Middlewares([Throttle::class, ApiAuth::class, Cors::class, Cache::class]))->wrap($app);
