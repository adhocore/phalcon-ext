<?php

namespace PhalconExt\Test;

use Ahc\Cli\IO\Interactor;
use Phalcon\Cli\Dispatcher;
use Phalcon\Cli\Router;
use Phalcon\Config;
use PhalconExt\Cli\Console;

class ConsoleTestCase extends TestCase
{
    protected $app;

    protected $tasks = [
        'main' => Cli\Task\MainTask::class,
        'some' => Cli\Task\SomeTask::class,
    ];

    /** @var string Mocked console user input */
    protected $mockedInput = '';

    /** @var string Buffered output from task action */
    protected $taskBuffer = null;

    protected static $in = __DIR__ . '/input';
    protected static $ou = __DIR__ . '/output';

    public function setUp()
    {
        \Phalcon\Di::reset();

        $di = include __DIR__ . '/../example/bootstrap.php';

        $di->setShared('dispatcher', Dispatcher::class);
        $di->setShared('router', Router::class);

        // A new instance of fully configured app :)
        $this->app = new MockConsole($di, 'test', '0.0.1-test');

        $di->setShared('interactor', $this->newInteractor($this->mockedInput));

        file_put_contents(static::$in, '');
        file_put_contents(static::$ou, '');

        $this->configure('console', ['tasks' => $this->tasks]);
        $this->app->initTasks(); // Late cofigured tasks are not auto inited.
    }

    public function tearDown()
    {
        unlink(static::$in);
        unlink(static::$ou);

        $this->taskBuffer = null;
    }

    protected function di(string $service = null)
    {
        if ($service) {
            return $this->app->getDI()->resolve($service);
        }

        return $this->app->getDI();
    }

    protected function newInteractor(string $in = '')
    {
        file_put_contents(static::$in, $in);

        return new Interactor(static::$in, static::$ou);
    }

    protected function config(string $path)
    {
        return $this->di('config')->path($path);
    }

    protected function configure(string $node, array $config): self
    {
        $config = array_replace_recursive($this->di('config')->toArray(), [$node => $config]);

        $this->di()->replace(['config' => new Config($config)]);

        return $this;
    }

    protected function assertTaskReturns($expected)
    {
        return $this->assertEquals($expected, $this->di('dispatcher')->getReturnedValue());
    }

    protected function assertTaskOutputs($expected)
    {
        return $this->assertContains($expected, $this->getOutputBuffer());
    }

    protected function getOutputBuffer()
    {
        if (null === $this->taskBuffer) {
            $this->taskBuffer = file_get_contents(static::$ou);
        }

        return $this->taskBuffer;
    }

    protected function bufferRun(callable $fn)
    {
        ob_start();

        $fn();
        $this->taskBuffer = ob_end_clean();
    }
}

class MockConsole extends Console
{
    // Dont already load them, we have their own tests somewhere else!
    protected $factoryTasks = [];
}
