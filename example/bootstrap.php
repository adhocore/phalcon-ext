<?php

use Ahc\Jwt\JWT;
use Phalcon\Cache\Frontend\None as CacheFront;
use Phalcon\Mvc\View;
use PhalconExt\Cache\Redis;
use PhalconExt\Db\Sqlite;
use PhalconExt\Di\FactoryDefault;
use PhalconExt\Mail\Mailer;
use PhalconExt\Validation\Validation;
use PhalconExt\View\Twig;

if (getenv('APP_ENV') !== 'test' && PHP_SAPI !== 'cli') {
    // For debug
    (new Phalcon\Debug)->listen(true, true);
}

$loader = (new Phalcon\Loader)
    ->registerNamespaces([
        'PhalconExt'       => __DIR__ . '/src/',
        'PhalconExt\\Test' => __DIR__ . '/tests/',
    ])
    ->registerClasses(require __DIR__ . '/../vendor/composer/autoload_classmap.php')
    ->register();

if (getenv('APP_ENV') !== 'test') {
    $loader->registerFiles(require __DIR__ . '/../vendor/composer/autoload_files.php')->loadFiles();
}

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
    return new Mailer($this->get('config')->toArray()['mail']);
});

$di->setShared('redis', function () {
    return new Redis(new CacheFront(['lifetime' => 0]));
});

// Since it is registered as FQCN, it is auto aliased when calling `registerAliases()`.
$di->setShared('validation', Validation::class);

$di->setShared('jwt', function () {
    $config = $this->get('config')->toArray()['apiAuth']['jwt'];

    return new JWT($config['keys'], $config['algo'], $config['maxAge'], $config['leeway'], $config['passphrase']);
});

// Aliasing is totally optional. This helps you to leverage power of di->resolve()
// Alternatively the service name in DI can be used as the name of constructor params in classes
//  to be resolved and that works without aliasing.
$di->registerAliases([
    // Alias => Known service
    View::class   => 'view',
    Twig::class   => 'twig',
    Mailer::class => 'mailer',
    JWT::class    => 'jwt',
    // Some like to call it validator!
    'validator'   => 'validation',
]);

return $di;
