<?php

namespace PhalconExt\Example;

use Ahc\Cron\Expression;
use Phalcon\Cli\Task;
use PhalconExt\Cli\Console;

class OtherTask extends Task
{
    public function onConstruct()
    {
        ($console = $this->console)
            ->addTask('other:main', 'Other task')
                ->tap($console)
                ->schedule('@always');
    }

    public function mainAction()
    {
        $this->interactor->boldGreen('Hello from other:main!', true);
    }
}
