<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test;

use Phalcon\Config\Config;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\Middlewares;

class WebTestCase extends TestCase
{
    protected $app;

    protected $middlewares = [];

    public function setUp(): void
    {
        // A new instance of fully configured app :)
        $this->app = include __DIR__ . '/../example/index.php';

        $this->middlewares = [];

        $this->resetDi();
    }

    protected function resetDi()
    {
        \Phalcon\Di\Di::reset();
        \Phalcon\Di\Di::setDefault($this->app->getDI());
    }

    protected function di(string $service = null)
    {
        if ($service) {
            return $this->app->getDI()->resolve($service);
        }

        return $this->app->getDI();
    }

    /**
     * This is stripped down barebone version for our example/ endpoints.
     */
    protected function doRequest(string $uri, array $parameters = [], array $headers = []): self
    {
        $method = 'GET';

        if ($uri[0] !== '/') {
            list($method, $uri) = explode(' ', $uri, 2);
        }

        $_GET                      = ['_url' => $uri];
        $_SERVER['REQUEST_METHOD'] = $method;
        $_REQUEST                  = $parameters;

        $method === 'POST' ? $_POST = $parameters : $_GET += $parameters;
        $_SERVER['QUERY_STRING']    = http_build_query($_GET);
        $_SERVER['REQUEST_URI']     = '/?' . $_SERVER['QUERY_STRING'];

        $headerKeys = [];
        foreach ($headers as $key => $value) {
            if (!in_array($key, ['Origin', 'Authorization'])) {
                $key = 'HTTP_' . str_replace('-', '_', $key);
            }
            $_SERVER[$headerKeys[] = strtoupper($key)] = $value;
        }

        $this->response = null;

        // Reset request/response!
        $this->di()->replace(['request' => new Request, 'response' => new Response]);

        ob_start();
        if ($this->middlewares) {
            (new Middlewares($this->middlewares))->wrap($this->app);
        } else {
            $this->app->handle($uri);
        }

        $content  = ob_get_clean();
        $response = $this->di('response');

        if (empty($response->getContent())) {
            $response->setContent($content);
        }
        foreach ($headerKeys as $key) {
            unset($_SERVER[$key]);
        }
        foreach ($parameters as $key) {
            unset($_REQUEST[$key], $_GET[$key], $_POST[$key]);
        }

        $this->response = $response;

        return $this;
    }

    protected function config(string $path)
    {
        return $this->di('config')->path($path);
    }

    protected function configure(string $node, array $config): self
    {
        $config = array_replace_recursive($this->di('config')->toArray(), [$node => $config]);

        $this->di()->replace(['config' => new Config($config)]);

        return $this;
    }

    protected function assertResponseOk(): self
    {
        $this->assertContains($this->responseCode(), [204, 200]);

        return $this;
    }

    protected function assertResponseNotOk(): self
    {
        $this->assertNotContains($this->responseCode(), [204, 200]);

        return $this;
    }

    protected function assertHeaderContains(string $header, string $value): self
    {
        $headers = $this->response->getHeaders()->toArray();

        $this->assertArrayHasKey($header, $headers);
        $this->assertStringContainsString($value, $headers[$header]);

        return $this;
    }

    protected function assertHeaderKeys(array $keys, bool $has = true): self
    {
        $headers = $this->response->getHeaders()->toArray();

        foreach ($keys as $key) {
            $has
                ? $this->assertArrayHasKey($key, $headers)
                : $this->assertArrayNotHasKey($key, $headers);
        }

        return $this;
    }

    protected function assertNotHeaderKeys(array $keys): self
    {
        return $this->assertHeaderKeys($keys, false);
    }

    protected function assertResponseContains(string $part): self
    {
        $this->assertStringContainsString($part, $this->response->getContent());

        return $this;
    }

    protected function assertStatusCode(int $code): self
    {
        $this->assertEquals($code, $this->responseCode());

        return $this;
    }

    protected function assertResponseJson(): self
    {
        $this->assertHeaderContains('Content-Type', 'application/json');

        return $this;
    }

    protected function responseCode(): int
    {
        $code = $this->response->getStatusCode();

        return $code === null ? 200 : (int) substr($code, 0, 3);
    }

    protected function assertResponseKey($key, $value = null)
    {
        $this->assertArrayHasKey($key, $json = $this->getJson());

        if (func_get_args() === 2) {
            $this->assertEquals($json[$key], $value);
        }

        return $this;
    }

    protected function assertResponseKeys(array $keys)
    {
        $json = $this->getJson();

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $json);
        }

        return $this;
    }

    protected function getJson()
    {
        $this->assertResponseJson();

        return json_decode($this->response->getContent(), true);
    }
}
