<?php

namespace PhalconExt\Test;

use Phalcon\Config;
use Phalcon\Http\Request;
use Phalcon\Http\Response;

class WebTestCase extends TestCase
{
    protected $app;

    public function setUp()
    {
        // A new instance of fully configured app :)
        $this->app = include __DIR__ . '/../example/index.php';

        $this->resetDi();
    }

    protected function resetDi()
    {
        \Phalcon\Di::reset();
        \Phalcon\Di::setDefault($this->app->getDI());
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
        if ($uri[0] !== '/') {
            list($method, $uri) = explode(' ', $uri, 2);
        }

        $parameters['_url']        = $uri;
        $_SERVER['REQUEST_METHOD'] = $method ?? 'GET';
        $_SERVER['QUERY_STRING']   = http_build_query($parameters);
        $_SERVER['REQUEST_URI']    = '/?' . $_SERVER['QUERY_STRING'];
        $_GET                      = $parameters;

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
        $this->app->handle($uri);
        $content = ob_get_clean();

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
        $this->assertContains($this->responseCode(), [null, 200]);

        return $this;
    }

    protected function assertResponseNotOk(): self
    {
        $this->assertNotContains($this->responseCode(), [null, 200]);

        return $this;
    }

    protected function assertHeaderContains(string $header, string $value): self
    {
        $headers = $this->response->getHeaders()->toArray();

        $this->assertArrayHasKey($header, $headers);
        $this->assertContains($value, $headers[$header]);

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
        $this->assertContains($part, $this->response->getContent());

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
}
