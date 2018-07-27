<?php

namespace PhalconExt\Cli\Middleware;

use Ahc\Cli\Input\Command;
use Phalcon\Cli\Console;
use Phalcon\Cli\Task;
use PhalconExt\Di\ProvidesDi;

/**
 * Factory middleware that injects Command and responds to `--help` or `--version`.
 */
class Factory
{
    use ProvidesDi;

    public function before(Console $console)
    {
        $rawArgv = $console->argv($raw = true);
        $argv    = $console->argv($raw = false);
        $app     = $console->app();
        $command = $app->commandFor($argv);

        if ($this->isVersion($rawArgv)) {
            return $command->showVersion();
        }

        if ($this->isGlobalHelp($rawArgv)) {
            return $app->showHelp();
        }

        if ($this->isHelp($rawArgv)) {
            return $command->showHelp();
        }

        $this->di()->setShared('command', $app->parse($argv));

        return true;
    }

    protected function isGlobalHelp(array $argv): bool
    {
        // For a specific help, it would be [cmd, task, action, --help]
        // If it is just [cmd, --help] then we deduce it is global help!

        $isGlobal = ($argv[1][0] ?? '-') === '-' && ($argv[2][0] ?? '-') === '-';

        return $isGlobal && $this->isHelp($argv);
    }

    protected function isHelp(array $argv): bool
    {
        return \in_array('--help', $argv) || \in_array('-h', $argv);
    }

    protected function isVersion(array $argv): bool
    {
        return \in_array('--version', $argv) || \in_array('-V', $argv);
    }
}
