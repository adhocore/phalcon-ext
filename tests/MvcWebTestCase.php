<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test;

class MvcWebTestCase extends WebTestCase
{
    public function setUp()
    {
        // A new instance of fully configured app :)
        $this->app = include __DIR__ . '/../example/mvc.php';

        $this->middlewares = [];

        $this->resetDi();
    }
}
