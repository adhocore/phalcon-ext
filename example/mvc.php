<?php

use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;
use PhalconExt\Http\Middleware\Cache;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Http\Middleware\Throttle;

// MVC app

$di = require_once __DIR__ . '/bootstrap.php';

$di->get('router')->setUriSource(Router::URI_SOURCE_GET_URL);

$app = new Application($di);

require_once __DIR__ . '/IndexController.php';

$di->get('router')->add('/', ['controller' => 'index', 'action' => 'index'])->setName('home');
$di->get('router')->add('/mail', ['controller' => 'index', 'action' => 'mail']);
$di->get('router')->add('/cors', ['controller' => 'index', 'action' => 'cors']);
$di->get('router')->add('/corsheader', ['controller' => 'index', 'action' => 'corsheader'], ['GET', 'OPTIONS']);

// Order: Throttle, Cors, Cache
(new Throttle)->boot();
(new Cors)->boot();
(new Cache)->boot();

echo $app->handle()->getContent();
