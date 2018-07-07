<?php

namespace PhalconExt\Test\Cli;

use Ahc\Cli\Application;
use PhalconExt\Test\ConsoleTestCase;

class ExtensionTest extends ConsoleTestCase
{
    public function test_app()
    {
        $this->assertInstanceOf(Application::class, $this->app->app());
    }

    public function test_argv()
    {
        $this->app->handle(['test', 'main', 'main', '--doo=doog']);

        $this->assertSame(['test', 'main', 'main', '--doo=doog'], $this->app->argv(true));
        $this->assertSame(['test', 'main:main', '--doo=doog'], $this->app->argv(false));
    }

    public function test_schedule()
    {
        $this->app->schedule('@daily');          // last task
        $this->app->schedule('@hourly', 'main'); // given task

        $this->assertNotEmpty($s = $this->app->scheduled());

        $this->assertArrayHasKey('main', $s);
        $this->assertArrayHasKey('some:main', $s);

        $this->assertSame('@daily', $s['some:main']);
        $this->assertSame('@hourly', $s['main']);
    }
}
