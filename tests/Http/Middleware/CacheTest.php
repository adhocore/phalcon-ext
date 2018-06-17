<?php

namespace PhalconExt\Test\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\Middleware\Cache;
use Phalcon\Db\Adapter;
use PhalconExt\Di\FactoryDefault;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Test\WebTestCase;

class CacheTest extends WebTestCase
{
    protected $cacheMw;

    public function setUp()
    {
        parent::setUp();

        $this->configure(['httpCache' => ['ttl' => 1, 'routes' => ['/']]]);

        $this->cacheMw = new Cache;
    }

    public function test_caches()
    {
        $this->app->before(function () {
            return $this->cacheMw->call($this->app);
        });

        // req #1 not cached
        $this->doRequest('/')->assertResponseOk()->assertNotHeaderKeys(['X-Cache', 'X-Cache-ID']);
    }

    /** @depends test_caches */
    public function test_uses_cache()
    {
        $this->app->after([$this->cacheMw, 'callAfter']);

        // req #2 cached
        $this->doRequest('/')->assertResponseOk()->assertHeaderKeys(['X-Cache', 'X-Cache-ID']);

        $this->di('redis')->delete($this->cacheMw->getLastKey());
    }
}
