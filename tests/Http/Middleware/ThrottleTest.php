<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Http\Middleware;

use PhalconExt\Http\Middleware\Throttle;
use PhalconExt\Test\WebTestCase;

class ThrottleTest extends WebTestCase
{
    protected $throttleMw;

    public function setUp()
    {
        parent::setUp();

        // Allow max 1 hits
        $this->configure('throttle', ['maxHits' => [1 => 1], 'checkUserAgent' => true]);

        $this->middlewares = [$this->throttleMw = new Throttle];
    }

    public function test_throttles()
    {
        // req #1 allowed
        $this->doRequest('/')->assertResponseOk();

        // req #2 not allowed
        $this->doRequest('/')->assertResponseNotOk()->assertStatusCode(429);

        $this->assertResponseContains('Too many requests');

        $this->di('redis')->delete($this->throttleMw->getRetryKey());
    }
}
