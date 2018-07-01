<?php

namespace PhalconExt\Cli;

use Ahc\Cli\ArgvParser;
use Phalcon\Cli\Task;
use Phalcon\DiInterface;
use PhalconExt\Di\ProvidesDi;

trait Extension
{
    use ProvidesDi;

    /** @var ArgvParser[] Registered tasks */
    protected $tasks = [];

    protected $argv = [];

    protected $name;

    protected $version = '0.0.1';

    public function __construct(DiInterface $di, string $name, string $version = '0.0.1')
    {
        parent::__construct($di);

        $di->setShared('application', $this);
        $di->setShared('console', $this);

        $this->name    = $name;
        $this->version = $version;
    }

    public function handle(array $argv = null)
    {
        $this->argv = $argv ?? $_SERVER['argv'];

        $this->bindEvents();

        $parameters   = $this->getTaskParameters($this->argv);
        $this->taskId = $parameters['task'] . ':' . $parameters['action'];

        parent::handle($parameters);
    }

    public function addTask(string $task, string $descr = '', bool $allowUnknown = false): ArgvParser
    {
        $taskId = \str_ireplace(['task', 'action'], '', $task);

        if (isset($this->tasks[$taskId])) {
            throw new \InvalidArgumentException(
                \sprintf('The task "%s" is already registered', $taskId)
            );
        }

        return $this->tasks[$taskId] = $this->newTask($taskId, $descr, $allowUnknown);
    }

    protected function getTaskParameters(array $argv)
    {
        $taskAction = [];
        foreach (\array_slice($argv, 1) as $i => $value) {
            if ($value[0] === '-') {
                break;
            }
            if (!isset($taskAction[1])) {
                $task = \str_ireplace(['task', 'action'], '', $value);
                unset($argv[$i]);

                $taskAction = \explode(':', $task, 2);
            }
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

    protected function newTask(string $taskId, string $descr = '', bool $allowUnknown = false)
    {
        $task = new ArgvParser($taskId, $descr, $allowUnknown);

        return $task->version($this->version)->onExit(function () {
            return false;
        });
    }

    protected function bindEvents()
    {
        $evm = $this->di('eventsManager');

        $evm->attach('dispatch', $this);
        $this->setEventsManager($evm);

        $this->di('dispatcher')->setEventsManager($evm);
    }

    public function beforeExecuteRoute()
    {
        if ($this->isGlobalHelp()) {
            return $this->globalHelp();
        }

        $parser = isset($this->tasks[$this->taskId])
            ? $this->tasks[$this->taskId]
            // Allow unknown as it is not explicitly defined with $cli->addTask()
            : $this->newTask($this->taskId, '', true);

        if ($this->isHelp()) {
            return $parser->emit('help');
        }

        $parser->parse($this->argv);

        $this->di()->setShared('argv', $parser);

        return true;
    }

    protected function isGlobalHelp(): bool
    {
        // For a specific help, it would be [cmd, task, action, --help]
        // If it is just [cmd, --help] then we deduce it is global help!

        $isGlobal = \substr($this->argv[1] ?? '-', 0, 1) === '-'
            && \substr($this->argv[2] ?? '-', 0, 1) === '-';

        return $isGlobal && $this->isHelp();
    }

    protected function isHelp(): bool
    {
        return \array_search('--help', $this->argv) || \array_search('-h', $this->argv);
    }

    protected function globalHelp()
    {
        $this->loadAllTasks();

        ($w = $this->di('cliWriter'))
            ->bold("{$this->name}, version {$this->version}", true)->eol()
            ->boldGreen('Commands:', true);

        $commands = [];
        foreach ($this->tasks as $task) {
            $commands[$task->getName()] = $task->getDesc();
        }

        $maxLen = \max(\array_map('strlen', \array_keys($commands)));

        foreach ($commands as $name => $desc) {
            $w->bold('  ' . \str_pad($name, $maxLen + 2))->comment($desc, true);
        }

        $w->eol()->yellow('Run `<command> --help` for specific help', true);

        return false;
    }

    protected function loadAllTasks()
    {
        if ($tasks = $this->di('config')->path('console.tasks')) {
            $classes = $tasks->toArray();
        } elseif ($this->di()->has('loader')) {
            $classes = \array_keys($this->di('loader')->getClasses());
        }

        foreach ($classes as $class) {
            if (\substr($class, -4) === 'Task') {
                // Force load!
                $this->di->resolve($class);
            }
        }
    }
}
