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

use PhalconExt\Http\Middleware\Cache;
use PhalconExt\Test\MvcWebTestCase;

class MvcCacheTest extends MvcWebTestCase
{
    protected $cacheMw;

    public function setUp()
    {
        parent::setUp();

        $this->configure('httpCache', ['ttl' => 1, 'routes' => ['/', '/', '/', '/']]);

        $this->middlewares = [$this->cacheMw = new Cache];
    }

    public function test_caches()
    {
        // req #1 not cached
        $this->doRequest('/')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertTrue($this->di('redis')->exists($this->cacheMw->getLastKey()));
    }

    public function test_uses_cache()
    {
        ob_end_clean();

        // req #2 cached
        $this->doRequest('/')->assertResponseOk()->assertHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->di('redis')->delete($this->cacheMw->getLastKey());
    }
}
