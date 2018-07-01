<?php

use Phalcon\Cli\Task;
use PhalconExt\Cli\Console;

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
            ->boldGreen->write('Hello from main:main!', true)
            ->bgRed->write('It allows known options only', true)
            ->comment->write('Name you entered is: ' . $this->argv->name, true);
    }

    public function runAction()
    {
        // Options specified in ::onConstruct() above.
        $this->cliWriter
            ->boldGreen->write('Hello from main:run!', true)
            ->bgRed->write('It allows unknown options too', true)
            ->bgRed->write('Input parameters:', true)
            ->comment->write(json_encode($this->argv->values(), JSON_PRETTY_PRINT), true);
    }
}
