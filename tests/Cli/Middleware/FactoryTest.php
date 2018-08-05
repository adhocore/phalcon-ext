<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Cli\Middleware;

use PhalconExt\Test\ConsoleTestCase;

class FactoryTest extends ConsoleTestCase
{
    public function test_loads_command()
    {
        $this->assertFalse($this->di()->has('command'));

        $task = $this->app->handle(['test', 'some', '-s', rand(1, 100)]);

        $this->assertTrue($this->di()->has('command'), 'Should be loaded by Factory Middleware');
    }

    public function test_app_help()
    {
        $this->app->handle(['test', '--help']);

        $this->assertTaskOutputs('test, version 0.0.1-test');
        $this->assertTaskOutputs('Commands:');
        $this->assertTaskOutputs('MainTask::main');
    }

    public function test_command_help()
    {
        $this->app->handle(['test', 'main', '--help']);

        $this->assertTaskOutputs('Command main, version 0.0.1-test');
        $this->assertTaskOutputs('MainTask::main');
        $this->assertTaskOutputs('main [OPTIONS...] [ARGUMENTS...]');
        $this->assertTaskOutputs('[dummy]');
        $this->assertTaskOutputs('[-d|--doo]');
    }

    public function test_version()
    {
        $this->app->handle(['test', '--version']);

        $this->assertTaskOutputs('0.0.1-test');
    }
}
