<?php

namespace PhalconExt\Test\Cli;

use PhalconExt\Cli\Middleware\Factory;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Test\ConsoleTestCase;

class MiddlewareTraitTest extends ConsoleTestCase
{
    public function test_middlewares()
    {
        $this->app->middleware(Awesome::class);
        $this->app->middlewares([Awesome::class, static::class]);

        $this->assertContains(Awesome::class, $this->app->middlewares());
        $this->assertContains(Factory::class, $this->app->middlewares());

        $this->app->handle(['test', 'main:main', 'dummy']);

        $this->assertTaskOutputs('You Are Awesome :)');
    }
}

/**
 * Sometimes you feel so low, someone need to remind you that you are awesome.
 *
 * One day this will be a real middleware in `src/Cli/Middleware`, but for now it is a test stub
 */
class Awesome
{
    use ProvidesDi;

    public function after()
    {
        $this->di('interactor')->eol(2)->greenBold('You Are Awesome :)', true);

        return true;
    }
}
