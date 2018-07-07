<?php

namespace PhalconExt\Test\Cli\Task;

use PhalconExt\Cli\Task\ScheduleTask;
use PhalconExt\Test\ConsoleTestCase;

class ScheduleTaskTest extends ConsoleTestCase
{
    protected $tasks = [
        'main'     => MainTask::class,
        'schedule' => ScheduleTask::class,
    ];

    public function test_no_list()
    {
        $this->app->handle(['test', 'schedule:list']);

        $this->assertTaskOutputs('No scheduled tasks');
    }

    public function test_list()
    {
        $this->app->schedule('@weekly', 'main')->handle(['test', 'schedule:list']);

        $this->assertTaskOutputs('Schedules:');
        $this->assertTaskOutputs('main');
        $this->assertTaskOutputs('@weekly');
    }

    public function test_no_run()
    {
        $this->app->handle(['test', 'schedule:run']);

        $this->assertTaskOutputs('No due tasks for now');
    }

    public function test_run()
    {
        $this->app->schedule('@always', 'main')->handle(['test', 'schedule:run']);

        $this->assertTaskOutputs('--------------------');
        $this->assertTaskOutputs('main:main');
        $this->assertTaskOutputs('PhalconExt\Test\Cli\Task\MainTask::mainAction');
    }
}
