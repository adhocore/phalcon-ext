<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Http;

use PhalconExt\Http\Middlewares;
use PhalconExt\Test\TestCase;

class MiddlewaresTest extends TestCase
{
    public function test_wrap_throws()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The app instance is not one of micro or mvc');

        (new Middlewares([]))->wrap(new class extends \Phalcon\Di\Injectable {
        });
    }
}
