<?php

use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;
use PhalconExt\Http\Middleware\Cache;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Http\Middleware\Throttle;
use PhalconExt\Http\Middlewares;

// MVC app

$di = require __DIR__ . '/bootstrap.php';

$di->get('router')->setUriSource(Router::URI_SOURCE_GET_URL);

$app = new Application($di);

require_once __DIR__ . '/IndexController.php';

$di->get('router')->add('/', ['controller' => 'index', 'action' => 'index'])->setName('home');
$di->get('router')->add('/mail', ['controller' => 'index', 'action' => 'mail']);
$di->get('router')->add('/cors', ['controller' => 'index', 'action' => 'cors']);
$di->get('router')->add('/corsheader', ['controller' => 'index', 'action' => 'corsheader'], ['GET', 'OPTIONS']);

// For test return the app instance
if (getenv('APP_ENV') === 'test') {
    return $app;
}

(new Middlewares([Throttle::class, Cors::class, Cache::class]))->wrap($app);
