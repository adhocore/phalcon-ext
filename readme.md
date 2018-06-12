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
```

#### upsert(string $table, array $data, array $criteria): bool
To insert of update as per given criteria
```php
$di->get('db')->upsert('users', ['name' => 'John'], ['username' => 'johnny']);
```

#### insertAsBulk(string $table, array $data): bool
Insert many items at once - in one query - no loop
```php
$di->get('db')->insertAsBulk('table', [
    ['name' => 'name1', 'status' => 'status1'],
    ['details' => 'detail2', 'name' => 'name2'], // columns dont need to be ordered or balanced
]);
```

#### countBy(string $table, array $criteria): int
Count rows in table by criteria
```php
$di->get('db')->countBy('table', ['name' => 'name1', 'status' => 'ok']);
```

### Db.Logger
Hook into the db as an event listener and log all the sql queries- binds are interpolated
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
```

#### registerAliases(array $aliases): self
Register aliases for di service so they can be resolved automatically
```php
$di->registerAliases([
    'TheAlias'                 => 'service',
    \Phalcon\Db\Adapter::class => 'db',
]);
```

#### resolve(string $class, array $parameters = []): mixed
Recursively resolve all dependencies of a given class FQCN and return new instance
```php
$instance = $di->resolve(\Some\Complex\ClassName::class, $parameters);
```

#### replace(array $services): self
Override a di service but keep backup so it may be restored if needed (great for tests)
```php
$di->replace(['service' => new \MockedService]);
```

#### restore(?string $service)
Restore the overridden services to their usual defaults
```php
$di->restore();          // All
$di->restore('service'); // One
```

### Di.ProvidesDi
#### di(?string $service): mixed
Easily resolve di services with this shortcut
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
### Logger.EchoLogger
#### log(string $message, int $type, array $context = [])
Echoes anything right away - but you can control formatting and log level
```php
$echo = $this->di(\PhalconExt\Logger\EchoLogger::class, ['config' => ['level' => Logger::INFO]]);
$echo->log('Message {a}', \Phalcon\Logger::INFO, ['a' => 'ok']);
```

### Logger.LogsToFile
#### log(string $message, int $type, array $context = [])
Delegate mundane file logging task to this trait thereby cutting down boilerplate codes
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
A Phalcon adapter/bridge/container/delegator (read: abcd) to swiftmailer
#### Setup
```php
$di->setShared('config', new \Phalcon\Config([
    'mail' => [
        'driver' => 'null',
        'from'   => [
            'name'  => 'Test',
            'email' => 'test@localhost',
        ],
    ],
]);

$di->setShared('mailer', function () {
    return new \PhalconExt\Mail\Mailer($this->get('config')->toArray()['mail']);
});
```

### Mail.Mail
A child of swiftmail message to allow attaching attachments without much ado
```php
$mail = $di->get('mailer')->newMail();
// Or from view template
$mail = $di->get('mailer')->newTemplateMail('view/file.twig', ['view' => 'params']);

$mail->setTo('test@localhost')->setSubject('Hi')->setBody('Hello')->mail();
```

### Mail.Mailable
#### mail()
Like Logger.LogsToFile above, but for mails
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
Automatically logs all sent mails into file as a swiftmailer event listener- you can choose log formats: `eml | html | json`

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
Ensures to warm up opcache for all files in given path well before file exceution
Opcache caches are specific to the sapi it is run. So for web, you need to have an endpoint
```php
$primer = new \PhalconExt\Util\OpcachePrimer;

$total = $primer->prime(['/path/to/project/src', '/path/to/project/app/', '/path/to/project/vendor/']);
```

---
### Validation.Validation
Validate data like we did in elsewhere- setting rules as .well-known array key=>value pairs

#### Setup
```php
$di->setShared('validation', \PhalconExt\Validation\Validation::class);
```

#### register(string $ruleName, $handler, string $message = ''): self
Register a new validation rule
```php
$di->get('validation')->register('gmail', function ($data) {
    return stripos($data['email'] ?? '', '@gmail.com') > 0;
}, 'Field :field must be an email with @gmail.com');
```

#### registerRules(string $ruleName, $handler, string $message = ''): self
Register many new validation rules
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
