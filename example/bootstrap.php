<?php

use PhalconExt\Db\Sqlite;
use PhalconExt\Di\FactoryDefault;
use PhalconExt\Mail\Mailer;
use PhalconExt\Validation\Validation;
use PhalconExt\View\Twig;
use Phalcon\Mvc\View;

$loader = (new Phalcon\Loader)
    ->registerNamespaces([
        'PhalconExt' => __DIR__ . '/src/',
    ])
    ->registerClasses(require_once __DIR__ . '/../vendor/composer/autoload_classmap.php')
    ->register();

$loader->registerFiles(require_once __DIR__ . '/../vendor/composer/autoload_files.php')->loadFiles();

$di = new FactoryDefault;

// Since it is registered as instantiated object, it is auto aliased when calling `registerAliases()`.
$di->setShared('loader', $loader);

// Setting config in DI is REQUIRED by this package.
$di->setShared('config', new Phalcon\Config(require __DIR__ . '/config.php'));

$di->setShared('db', function () {
    $config = $this->get('config')->toArray();

    // PS: If you had already extended your db adapter then imoort trait `PhalconExt\Db\Extension`
    return (new Sqlite($config['database']))->registerLogger($config['sqllogger']);
});

$di->setShared('view', function () {
    return (new View)
        ->setViewsDir($this->get('config')->toArray()['view']['dir'])
        ->registerEngines([
            '.twig' => 'twig',
        ]);
});

$di->setShared('twig', function () {
    $twig = new Twig($this->get('view'), $this);

    // Here you can:
    // $twig->addFilter(...)
    // $twig->addExtension(...)

    return $twig;
});

$di->setShared('mailer', function () {
    return (new Mailer($this->get('config')->toArray()['mail']));
});

// Since it is registered as FQCN, it is auto aliased when calling `registerAliases()`.
$di->setShared('validation', Validation::class);

// Aliasing is totally optional. This helps you to leverage power of di->resolve()
// Alternatively the service name in DI can be used as the name of constructor params in classes
//  to be resolved and that works without aliasing.
$di->registerAliases([
    // Alias => Known service
    View::class   => 'view',
    Twig::class   => 'twig',
    Mailer::class => 'mailer',
    // Some like to call it validator!
    'validator'   => 'validation'
]);

return $di;
