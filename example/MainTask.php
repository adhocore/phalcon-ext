<?php

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function onConstruct()
    {
        $this->console->addTask('main:run', 'MainTask@run', true)
            ->option('-c --config <path>', 'Config file');
    }

    public function mainAction()
    {
        // Options specified in example/cli already.
        $this->cliWriter
            ->boldGreen('Hello from main:main!', true)
            ->bgRed('It allows known options only', true)->eol()
            ->comment('Name you entered is: ' . $this->argv->name, true);
    }

    public function runAction()
    {
        // Options specified in ::onConstruct() above.
        $this->cliWriter
            ->boldGreen('Hello from main:run!', true)
            ->bgRed('It allows unknown options too', true)->eol()
            ->boldCyan('Input parameters:', true)->eol()
            ->comment(json_encode($this->argv->values(), JSON_PRETTY_PRINT), true);
    }
}
