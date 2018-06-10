<?php

use PhalconExt\Http\Middleware\Cors;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;

# MVC app

$di = require_once __DIR__ . '/bootstrap.php';

$di->get('router')->setUriSource(Router::URI_SOURCE_GET_URL);

$app = new Application($di);

require_once __DIR__ . '/IndexController.php';

$di->get('router')->add('/mail', ['controller' => 'index', 'action' => 'mail']);
$di->get('router')->add('/cors', ['controller' => 'index', 'action' => 'cors']);
$di->get('router')->add('/corsheader', ['controller' => 'index', 'action' => 'corsheader'], ['GET', 'OPTIONS']);

// Cors
$evm = $di->get('eventsManager');
$evm->attach('dispatch:beforeExecuteRoute', new Cors);
$di->get('dispatcher')->setEventsManager($evm);

echo $app->handle()->getContent();
