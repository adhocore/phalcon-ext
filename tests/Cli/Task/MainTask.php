<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Cli\Task;

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function onConstruct()
    {
        $this->console
            ->command('main', 'MainTask::main', true)
                ->arguments('[dummy]')
                ->option('-d --doo [doo]', 'Doo');
    }

    public function mainAction()
    {
        $this->interactor->write(__METHOD__);

        return $this->command->dummy;
    }
}

class SomeTask extends Task
{
    public function onConstruct()
    {
        $this->console
            ->command('some:main', 'SomeTask::main', true)
                ->option('-s --some [some]', 'Some');
    }

    public function mainAction()
    {
        $this->interactor->write(__METHOD__);

        return $this->command->some;
    }
}
