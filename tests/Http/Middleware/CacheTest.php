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
use PhalconExt\Test\WebTestCase;

class CacheTest extends WebTestCase
{
    protected $cacheMw;

    public function setUp(): void
    {
        parent::setUp();

        $this->configure('httpCache', ['ttl' => 1, 'routes' => ['/', '/', '/', '/']]);

        $this->middlewares = [$this->cacheMw = new Cache];
    }

    public function test_caches_and_uses_cache()
    {
        // req #1 not cached
        $this->doRequest('/')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertTrue($this->di('redis')->exists($this->cacheMw->getLastKey()));

        // req #2 cached
        $this->doRequest('/')->assertResponseOk()->assertHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->di('redis')->delete($this->cacheMw->getLastKey());
    }

    public function test_doesnt_cache_disallowed_route()
    {
        // req #1 not cached
        $this->doRequest('/logger')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertFalse($this->di('redis')->exists($this->cacheMw->getLastKey()));

        // req #2 still not cached
        $this->doRequest('/logger')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertFalse($this->di('redis')->exists($this->cacheMw->getLastKey()));
    }

    public function test_doesnt_cache_POST()
    {
        $this->app->post('/post', function () {
            return date('Y-m-d HIs');
        });

        // req #1 not cached
        $this->doRequest('POST /post')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertFalse($this->di('redis')->exists($this->cacheMw->getLastKey()));

        // req #2 still not cached
        $this->doRequest('POST /post')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->assertFalse($this->di('redis')->exists($this->cacheMw->getLastKey()));
    }
}
