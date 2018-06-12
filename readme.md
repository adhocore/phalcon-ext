## adhocore/phalcon-ext

Miscellaneous phalcon adapters, extensions and utilities

[![Travis Build](https://travis-ci.com/adhocore/phalcon-ext.svg?branch=master)](https://travis-ci.com/adhocore/phalcon-ext?branch=master)
[![Latest Version](https://img.shields.io/github/release/adhocore/phalcon-ext.svg?style=flat-square)](https://github.com/adhocore/phalcon-ext/releases)
[![Scrutinizer CI](https://img.shields.io/scrutinizer/g/adhocore/phalcon-ext.svg?style=flat-square)](https://scrutinizer-ci.com/g/adhocore/phalcon-ext/?branch=master)
[![Codecov branch](https://img.shields.io/codecov/c/github/adhocore/phalcon-ext/master.svg?style=flat-square)](https://codecov.io/gh/adhocore/phalcon-ext)
[![StyleCI](https://styleci.io/repos/136166947/shield)](https://styleci.io/repos/136166947)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

## Installation

```sh
composer require adhocore/phalcon-ext
```

---
### Cache.Redis

Extends `Phalcon\Cache\Backend\Redis` to allow access over the underlying redis binding.

#### Setup

```php
$di->setShared('redis', function () {
    return new \PhalconExt\Cache\Redis(new \Phalcon\Cache\Frontend\None(['lifetime' => 0]));
});

// Call native \Redis methods like:
$di->get('redis')->getConnection()->hGet();
$di->get('redis')->getConnection()->info();
```

### Db.Extension

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
    'database' => [
        'driver' => 'sqlite',
        'dbname' => __DIR__ . '/.var/db.db',
    // ... other options (see phalcon &/or pdo docs)
    ],
]);

$di->setShared('db', function () {
    // Can use Mysql or Postgresql too
    return (new \PhalconExt\Db\Sqlite($this->get('config')->toArray()['database']));
});

// Or if you have your own already, just use the trait
class YourDb extends \Phalcon\Db\Adapter
{
    use PhalconExt\Db\Extension;
}
```

#### upsert(string $table, array $data, array $criteria): bool
Insert or update data row in given table as per given criteria.
```php
$di->get('db')->upsert('users', ['name' => 'John'], ['username' => 'johnny']);
```

#### insertAsBulk(string $table, array $data): bool
Insert many items at once - in one query - no loop.
```php
$di->get('db')->insertAsBulk('table', [
    ['name' => 'name1', 'status' => 'status1'],
    ['details' => 'detail2', 'name' => 'name2'], // columns dont need to be ordered or balanced
]);
```

#### countBy(string $table, array $criteria): int

Count rows in table by criteria.

```php
$di->get('db')->countBy('table', ['name' => 'name1', 'status' => 'ok']);
```

### Db.Logger
Hook into the db as an event listener and log all the sql queries- binds are interpolated.
```php
$di->setShared('config', new \Phalcon\Config([
    'sqllogger' => [
        'enabled'        => true,
        'logPath'        => __DIR__ . '/.var/sql/', // directory
        'addHeader'      => true,
        'backtraceLevel' => 5,
        'skipFirst'      => 2,
    ],
]);

$di->get('db')->registerLogger($di->get('config')->toArray()['sqllogger']);
```

---
### Di.Extension

#### Setup

```php
$di = new \PhalconExt\Di\FactoryDefault;

// Or if you have your own already, just use the trait
class YourDi extends \Phalcon\Di
{
    use PhalconExt\Di\Extension;
}
```

#### registerAliases(array $aliases): self

Register aliases for di service so they can be resolved automatically by name &/or typehints.

```php
$di->registerAliases([
    'TheAlias'                 => 'service',
    \Phalcon\Db\Adapter::class => 'db',
]);
```

#### resolve(string $class, array $parameters = []): mixed

Recursively resolve all dependencies of a given class FQCN and return new instance.

```php
$instance = $di->resolve(\Some\Complex\ClassName::class, $parameters);
```

#### replace(array $services): self

Override a di service but keep backup so it may be restored if needed (great for tests)

```php
$di->replace(['service' => new \MockedService]);
```

#### restore(?string $service)

Restore the overridden services to their usual defaults.

```php
$di->restore();          // All
$di->restore('service'); // One
```

### Di.ProvidesDi

#### di(?string $service): mixed

Easily resolve di services with this shortcut.

```php
class AnyClass
{
    use \PhalconExt\Di\ProviesDi;

    public function anyFn()
    {
        $di = $this->di();
        $db = $this->di('db');
    }
}
```

---
### Http.BaseMiddleware

A base implementation for middlewares on top of which you can create your own middlewares.
See an example for Ajax middleware:

```php
$di->setShared('config', new \Phalcon\Config([
    'ajax' => [
        'uriPrefix' => '/ajax',
    ],
]);

class Ajax extends \PhalconExt\Http\BaseMiddleware
{
    /** @var string */
    protected $configKey = 'ajax';

    /**
     * For any uri starting with `/ajax`, allow if only it is real ajax request.
     *
     * Register as before handler because we will abort before actual exceution if not ajax.
     *
     * @return bool
     */
    public function before(Phalcon\Http\Request $request, Phalcon\Http\Response $response): bool
    {
        list(, $uri) = $this->getRouteNameUri();

        if (\stripos($uri, $this->config['uriPrefix']) !== 0) {
            return true;
        }

        if (!$request->isAjax()) {
            return $this->abort(400);
        }

        return true;
    }
}

// Usage: same for micro or mvc app :)
(new Ajax)->boot();
```

#### Http.Middleware.Cache

Caches output for requests to boost performance heavily. Requires redis service.
Currently by design only GET requests are cached and this might change.

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
    'httpCache' => [
        // cache life- time to live in mintues
        'ttl'       => 60,
        // White listed uri/routes to enable caching
        'routes'    => [
            // for absolute uri, prepend forward `/`
            '/content/about-us',
            // or you can use route name without a `/`
            'home',
        ],
    ],
]);

// Usage: same for micro or mvc app :)
(new \PhalconExt\Http\Middleware\Cache)->boot();
```

#### Http.Middleware.Cors

Enables cors with preflight for configured origins and request options.

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
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
]);

// Usage: same for micro or mvc app :)
(new \PhalconExt\Http\Middleware\Cors)->boot();
```

#### Http.Middleware.Throttle

Throttles the flooded requests as per time and quota of your choosing. Requires redis service.

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
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
]);

// Usage: same for micro or mvc app :)
(new \PhalconExt\Http\Middleware\Throttle)->boot();
```

---
### Logger.EchoLogger

#### log(string $message, int $type, array $context = [])

Echoes anything right away - but you can control formatting and log level.

```php
$echo = $this->di(\PhalconExt\Logger\EchoLogger::class, ['config' => ['level' => Logger::INFO]]);
$echo->log('Message {a}', \Phalcon\Logger::INFO, ['a' => 'ok']);
```

### Logger.LogsToFile

#### log(string $message, int $type, array $context = [])

Delegate mundane file logging task to this trait thereby cutting down boilerplate codes.

```php
class AnyClass
{
    use \PhalconExt\Logger\LogsToFile;

    protected $fileExtension = '.log';

    public function anyFn()
    {
        $this->activate('/path/to/log/dir/');

        $this->log('Some message', \Phalcon\Logger::INFO);
    }
}
```

---
### Mail.Mailer

A Phalcon adapter/bridge/container/delegator (read: abcd) to swiftmailer.

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
    'mail' => [
        'driver' => 'null',
        'from'   => [
            'name'  => 'Test',
            'email' => 'test@localhost',
        ],

        // for driver 'smtp':
        'host'       => 'smtp.server.com',
        'port'       => 425,
        'encryption' => true,
        'username'   => 'user',
        'password'   => 'pass',

        // for driver sendmail only (optional)
        'sendmail' => '/sendmail/binary',
    ],
]);

$di->setShared('mailer', function () {
    return new \PhalconExt\Mail\Mailer($this->get('config')->toArray()['mail']);
});
```

### Mail.Mail

A child of swiftmail message to allow attaching attachments without much ado.

```php
$mail = $di->get('mailer')->newMail();
// Or from view template
$mail = $di->get('mailer')->newTemplateMail('view/file.twig', ['view' => 'params']);

$mail->setTo('test@localhost')->setSubject('Hi')->setBody('Hello')->mail();
```

### Mail.Mailable

#### mail()

Like Logger.LogsToFile above, but for mails.

```php
class AnyClass
{
    use \PhalconExt\Mail\Mailable;

    public function anyFn()
    {
        $this->mail('test@local', 'Hi', ['body' => 'Hello']);
        $this->mail('test@local', 'Hi', ['template' => 'view/file.twig', 'params' => ['key' => 'value']]);
    }
}
```

### Mail.Logger

Automatically logs all sent mails into file as a swiftmailer event listener- you can choose log formats: `eml | html | json`.

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
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
]);

// When setting mailer, include config `mail>logger` and it is auto set up.
$di->setShared('mailer', function () {
    return new \PhalconExt\Mail\Mailer($this->get('config')->toArray()['mail']);
});
```

---
### Util.OpcachePrimer

#### prime(array $paths): int

Ensures to warm up opcache for all files in given path well before file exceution.
Opcache caches are specific to the sapi it is run. So for web, you need to have an endpoint

```php
$primer = new \PhalconExt\Util\OpcachePrimer;

$total = $primer->prime(['/path/to/project/src', '/path/to/project/app/', '/path/to/project/vendor/']);
```

---
### Validation.Validation

Validate data like we did in elsewhere- setting rules as .well-known strings or key=>value pairs (array).

#### Setup

```php
$di->setShared('validation', \PhalconExt\Validation\Validation::class);
```

#### register(string $ruleName, $handler, string $message = ''): self

Register a new validation rule.

```php
$di->get('validation')->register('gmail', function ($data) {
    return stripos($data['email'] ?? '', '@gmail.com') > 0;
}, 'Field :field must be an email with @gmail.com');
```

#### registerRules(array $ruleHandlers, array $messages = []): self

Register many new validation rules at once.

```php
$di->get('validation')->registerRules([
    'rule1' => function($data) { return true; },
    'rule1' => function($data) { return false; },
], [
    'rule1' => 'message1',
    'rule2' => 'message2'
]);
```

#### Usage

```php
$validation = $this->di('validation');

$rules = [
    // Can be string
    'id'    => 'required|length:min:1;max:2;|in:domain:1,12,30',
    // Can be an array too
    'email' => [
        'required' => true,
        'gmail'    => true,
    ],
    // validate if only exist in dataset
    'xyz' => 'length:5|if_exist',
];

// Validate against empty data
$validation->run($rules, []);

$pass = $validation->pass(); // false
$fail = $validation->fail(); // true

$errors = $validation->getErrorMessages(); // array
```

---
### View.Twig

Use twig view natively in Phalcon

#### Setup

```php
$di->setShared('config', new \Phalcon\Config([
    'view' => [
        'dir' => __DIR__ . '/view/',
    ],
    // Required
    'twig' => [
        'view_dirs'   => [__DIR__ . '/view/'], // array
        'auto_reload' => getenv('APP_ENV') !== 'prod',
        'cache'       => __DIR__ . '/.var/view/',
        // ... other options (see twig docs)
    ],
]);

// You must have view setup with twig engine enabled.
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
```

#### Usage

```php
// standalone
$di->get('twig')->render('template.twig', ['view' => 'params']);
// or as view
$di->get('twig')->render('template.twig', ['view' => 'params']); // .twig is optional
```

---

You can also see the [example](./example) codes for all these. Read [more](./example/readme.md).
