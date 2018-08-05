<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Cli;

use PhalconExt\Cli\Console;
use PhalconExt\Cli\Extension as CliExtension;
use PhalconExt\Cli\MiddlewareTrait;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Test\ConsoleTestCase;

class ConsoleTest extends ConsoleTestCase
{
    public function test()
    {
        $this->assertInstanceOf(Console::class, $this->app);

        $this->assertNotEmpty($traits = class_uses_deep($this->app, false));

        $this->assertArrayHasKey(ProvidesDi::class, $traits);
        $this->assertArrayHasKey(CliExtension::class, $traits);
        $this->assertArrayHasKey(MiddlewareTrait::class, $traits);

        $this->assertTrue(method_exists($this->app, 'doHandle'));
        $this->assertTrue(method_exists($this->app, 'bindEvents'));
        $this->assertTrue(method_exists($this->app, 'middleware'));
    }
}
