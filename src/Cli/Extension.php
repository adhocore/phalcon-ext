<?php

namespace PhalconExt\Cli;

use Ahc\Cli\ArgvParser;
use PhalconExt\Di\ProvidesDi;

trait Extension
{
    use ProvidesDi;

    /** @var ArgvParser[] Registered tasks */
    protected $tasks = [];

    protected $argv = [];

    protected $version = '0.0.1';

    public function version(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function handle(array $argv = null)
    {
        $this->argv = $argv ?? $_SERVER['argv'];

        $this->di()->setShared('application', $this);

        $this->bindEvents();

        $parameters   = $this->getTaskParameters($this->argv);
        $this->taskId = $parameters['task'] . ':' . $parameters['action'];

        parent::handle($parameters);
    }

    public function addTask(string $task, string $descr = null, bool $allowUnknown = false): ArgvParser
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
            if (!isset($taskId[1])) {
                $taskAction[] = \str_ireplace(['task', 'action'], '', $value);
                unset($argv[$i]);
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

    protected function newTask(string $taskId, string $descr = null, bool $allowUnknown = false)
    {
        $task = new ArgvParser($taskId, $descr, $allowUnknown);

        return $task->version($this->version)->arguments('<task> [action:main]');
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
        $parser = isset($this->tasks[$this->taskId])
            ? $this->tasks[$this->taskId]
            // Allow unknown as it is not explicitly defined with $cli->addTask()
            : $this->newTask($this->taskId, '', true);

        $parser->parse($this->argv);

        $this->di()->setShared('argv', $parser);

        return true;
    }
}
