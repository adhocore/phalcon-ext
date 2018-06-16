<?php

use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\View\Simple as SimpleView;
use PhalconExt\Http\Middleware\Cache;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Http\Middleware\Throttle;

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

require_once __DIR__ . '/MicroController.php';

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
    ->mapVia('corsheader', 'corsHeaderAction', ['GET', 'OPTIONS'])
);

// Order: Throttle, Cors, Cache
(new Throttle)->boot();
(new Cors)->boot();
(new Cache)->boot();

$app->notFound(function () use ($di) {
    return $di->get('response')->setContent('')->setStatusCode(404);
});

if (getenv('APP_ENV') === 'test') {
    return $app;
}

$app->handle();
