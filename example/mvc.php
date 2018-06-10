<?php

use Phalcon\Mvc\Application;
use Phalcon\Mvc\Router;

# MVC app

$di = require_once __DIR__ . '/bootstrap.php';

$di->get('router')->setUriSource(Router::URI_SOURCE_GET_URL);

$app = new Application($di);

require_once __DIR__ . '/IndexController.php';

$di->get('router')->add('/mail', ['controller' => 'index', 'action' => 'mail']);

echo $app->handle()->getContent();
