<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Cli;

use Ahc\Cli\Application;
use Ahc\Cli\Input\Command;
use Ahc\Cli\IO\Interactor;
use Phalcon\Cli\Task;
use Phalcon\Di\DiInterface;
use PhalconExt\Cli\Task\ScheduleTask;
use PhalconExt\Di\ProvidesDi;

trait Extension
{
    use ProvidesDi;
    use MiddlewareTrait;

    /** @var array Tasks namespaces */
    protected $namespaces = [];

    /** @var array Tasks provided by package already */
    protected $factoryTasks =  [
        'schedule' => ScheduleTask::class,
    ];

    /** @var array Scheduled taskIds mapped to schedule time */
    protected $scheduled = [];

    /** @var array Raw argv sent to handle() [OR read from] */
    protected $rawArgv = [];

    /** @var array Normalized argv */
    protected $argv = [];

    /** @var string */
    protected $lastCommand;

    public function __construct(DiInterface $di, string $name, string $version = '0.0.1')
    {
        parent::__construct($di);

        $di->setShared('console', $this);
        $di->setShared('interactor', Interactor::class);

        $this->initialize($name, $version);
        $this->bindEvents($this);
    }

    protected function initialize(string $name, string $version)
    {
        $this->app = new Application($name, $version, function () {
            return false;
        });

        $this->initTasks();
    }

    /**
     * Get the console Application.
     *
     * @return Application The instance of Ahc\Cli\Application.
     */
    public function app(): Application
    {
        return $this->app;
    }

    /**
     * Get the raw or processed argv values.
     *
     * By processed it means the task/action segment has been merged or shifted.
     *
     * @param bool $raw If true default raw values are returned, otherwise processed values.
     *
     * @return array
     */
    public function argv(bool $raw = true): array
    {
        if ($raw) {
            return $this->rawArgv;
        }

        return $this->argv;
    }

    /**
     * Handle console request.
     *
     * @param array|null $argv
     *
     * @return mixed But mostly the task instance that was executed.
     */
    public function handle(array $argv = null)
    {
        $this->rawArgv = $argv ?? $_SERVER['argv'];

        $params = $this->getTaskParameters($this->rawArgv);

        // Normalize in the form: ['app', 'task:action', 'param1', 'param2', ...]
        $this->argv = \array_merge(
            [$argv[0] ?? null, $params['task'] . ':' . $params['action']],
            $params['params']
        );

        return $this->doHandle($params);
    }

    /**
     * Handle cli request.
     *
     * @param array $parameters ['task' => ..., 'action' => ..., 'params' => []]
     *
     * @return mixed But mostly the task instance that was executed.
     */
    public function doHandle(array $parameters)
    {
        if (isset($this->namespaces[$parameters['task']])) {
            $parameters['task'] = $this->namespaces[$parameters['task']];
        }

        return parent::handle($parameters);
    }

    /**
     * Register a new command to be managed/scheduled by console.
     *
     * This allows you to define args/options which are not only auto validated but
     * injected to DI container by the name `command`.
     *
     * (You can still run tasks without adding it here)
     *
     * @param string $command      Preferred format is 'task:action'.
     *                             (for 'main' action, it can be 'task' only)
     * @param string $descr        Task description in short.
     * @param bool   $allowUnknown Whether to allow unkown options.
     *
     * @return Command The cli command for which you can define args/options fluenlty.
     */
    public function command(string $command, string $descr = '', bool $allowUnknown = false): Command
    {
        $this->lastCommand = $command;

        if (\strpos($command, ':main')) {
            $alias = \str_replace(':main', '', $command);
        }

        if (\strpos($command, ':') === false) {
            $alias = $command . ':main';
        }

        return $this->app->command($command, $descr, $alias ?? '', $allowUnknown);
    }

    /**
     * Schedule a command to run at the time when given cron expression evaluates truthy.
     *
     * @param string $cronExpr Eg: `@hourly` (Take a look at Ahc\Cli\Expression for predefined values)
     * @param string $command  This is optional (by default it schedules last command added via `command()`)
     *                         If given, the name should match the name you passed to `addTask($name)`
     *
     * @return self
     */
    public function schedule(string $cronExpr, string $command = ''): self
    {
        $command = $command ?: $this->lastCommand;

        $this->scheduled[$command] = $cronExpr;

        return $this;
    }

    /**
     * Get all the scheduled items.
     *
     * @return array
     */
    public function scheduled(): array
    {
        return $this->scheduled;
    }

    protected function getTaskParameters(array $argv)
    {
        $taskAction = [];
        \array_shift($argv);

        foreach ($argv as $i => $value) {
            if ($value[0] === '-' || isset($taskAction[1])) {
                break;
            }

            $taskAction = \array_merge($taskAction, \explode(':', $value, 2));
            unset($argv[$i]);
        }

        // Respect phalcon default.
        $taskAction += ['main', 'main'];

        return [
            'task'   => $taskAction[0],
            'action' => $taskAction[1],
            // For BC, still send params to handle()
            'params' => \array_values($argv),
        ];
    }

    /**
     * Inits tasks. It is done automatically if you have listed them in `console.tasks` config.
     *
     * @return self
     */
    public function initTasks(): self
    {
        foreach ($this->getTaskClasses() as $name => $class) {
            if (!$this->di()->has($class)) {
                // Force load!
                $this->di($class);
            }

            $this->namespaces[$name] = \preg_replace('#Task$#', '', $class);
        }

        return $this;
    }

    protected function getTaskClasses(): array
    {
        if ($tasks = $this->di('config')->path('console.tasks')) {
            $tasks = $tasks->toArray();
        }

        return $this->factoryTasks + ($tasks ?: []);
    }
}
