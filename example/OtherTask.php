<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Example;

use Phalcon\Cli\Task;

class OtherTask extends Task
{
    public function onConstruct()
    {
        ($console = $this->console)
            ->command('other:main', 'Other task')
                ->tap($console)
                ->schedule('@always');
    }

    public function mainAction()
    {
        $this->interactor->boldGreen('Hello from other:main!', true);
    }
}
