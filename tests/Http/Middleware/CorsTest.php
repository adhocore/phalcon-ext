<?php

namespace PhalconExt\Test\Http\Middleware;

use Phalcon\Http\Request;
use PhalconExt\Http\Middleware\Cors;
use PhalconExt\Test\WebTestCase;

class CorsTest extends WebTestCase
{
    protected $corsMw;

    public function setUp()
    {
        parent::setUp();

        // $this->configure(['cors' => ['']]);

        $this->app->before($this->corsMw = new Cors);
    }

    public function test_cors_headers()
    {
        $headers = ['Access-Control-Request-Method' => 'GET', 'Origin' => 'http://127.0.0.1:1234'];

        // Preflight request (OPTIONS)
        $this->doRequest('OPTIONS /corsheader', [], $headers)
            ->assertResponseOk()
            ->assertHeaderKeys(['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'])
            ->assertHeaderKeys(['Access-Control-Max-Age', 'Access-Control-Allow-Methods']);

        unset($headers['Access-Control-Request-Method']);

        // Normal request (GET)
        $this->doRequest('/corsheader', [], $headers)
            ->assertResponseOk()
            ->assertResponseJson()
            ->assertHeaderKeys(['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'])
            ->assertNotHeaderKeys(['Access-Control-Max-Age', 'Access-Control-Allow-Methods']);
    }
}
