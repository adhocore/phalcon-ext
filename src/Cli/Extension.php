<?php

namespace PhalconExt\Cli;

use Ahc\Cli\Application;
use Ahc\Cli\Input\Command;
use Ahc\Cli\IO\Interactor;
use Phalcon\Cli\Task;
use Phalcon\DiInterface;
use PhalconExt\Cli\Task\ScheduleTask;
use PhalconExt\Di\ProvidesDi;

trait Extension
{
    use ProvidesDi;
    use MiddlewareTrait;

    /** @var array Tasks namespaces */
    protected $namespaces = [];

    /** @var array Tasks provided by package already */
    protected $factoryTasks = [];

    /** @var array Scheduled taskIds mapped to schedule time */
    protected $scheduled = [];

    /** @var array Raw argv sent to handle() [OR read from $_SERVER] */
    protected $rawArgv = [];

    /** @var array Normalized argv */
    protected $argv = [];

    /** @var string */
    protected $lastTask;

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

        $this->factoryTasks = [
            'schedule' => ScheduleTask::class,
        ];

        $this->initTasks();
    }

    public function app()
    {
        return $this->app;
    }

    public function argv(bool $raw = true): array
    {
        if ($raw) {
            return $this->rawArgv;
        }

        return $this->argv;
    }

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

    public function doHandle(array $parameters)
    {
        if (isset($this->namespaces[$parameters['task']])) {
            $parameters['task'] = $this->namespaces[$parameters['task']];
        }

        return parent::handle($parameters);
    }

    public function addTask(string $task, string $descr = '', bool $allowUnknown = false): Command
    {
        $this->lastTask = $taskId = \str_ireplace(['task', 'action'], '', $task);

        if (\strpos($task, ':main')) {
            $alias = \str_replace(':main', '', $taskId);
        }

        if (\strpos($task, ':') === false) {
            $alias = $taskId . ':main';
        }

        return $this->app->command($taskId, $descr, $alias ?? '', $allowUnknown);
    }

    public function schedule(string $cronExpr, string $taskId = ''): self
    {
        $taskId = $taskId ?: $this->lastTask;

        $this->scheduled[$taskId] = $cronExpr;

        return $this;
    }

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

    public function initTasks()
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
