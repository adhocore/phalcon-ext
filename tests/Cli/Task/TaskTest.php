<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Cli\Middleware;

use PhalconExt\Test\ConsoleTestCase;

class TaskTest extends ConsoleTestCase
{
    public function test_main()
    {
        $task = $this->app->handle(['test', 'main', '--', $dummy = rand(1, 100)]);

        $this->assertTaskReturns($dummy);
        $this->assertTaskOutputs('PhalconExt\Test\Cli\Task\MainTask::mainAction');
    }

    public function test_main_main()
    {
        $task = $this->app->handle(['test', 'main', 'main', $dummy = rand(1, 100)]);

        $this->assertTaskReturns($dummy);
    }

    public function test_main_colon()
    {
        $task = $this->app->handle(['test', 'main:main', $dummy = rand(1, 100)]);

        $this->assertTaskReturns($dummy);
    }
}
