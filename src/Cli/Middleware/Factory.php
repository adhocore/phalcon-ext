<?php

namespace PhalconExt\Cli\Middleware;

use Ahc\Cli\Helper\OutputHelper;
use Ahc\Cli\Input\Command;
use Ahc\Cli\Output\Writer;
use Phalcon\Cli\Console;
use Phalcon\Cli\Task;
use Phalcon\DiInterface;
use PhalconExt\Di\ProvidesDi;

/**
 * Factory middleware that injects Parser instance and responses to `--help` or `--version`.
 */
class Factory
{
    use ProvidesDi;

    public function before(Console $console)
    {
        $argv    = $console->argv();
        $app     = $console->app();
        $command = $app->commandFor($argv);

        if ($this->isVersion($argv)) {
            return $command->showVersion();
        }

        if ($this->isGlobalHelp($argv)) {
            return $app->showHelp();
        }

        if ($this->isHelp($argv)) {
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
