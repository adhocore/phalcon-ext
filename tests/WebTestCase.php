<?php

namespace PhalconExt\Test;

use PHPUnit\Framework\TestCase;

class WebTestCase extends TestCase
{
    protected $app;

    public function setUp()
    {
        // A new instance of fully configured app :)
        $this->app = include __DIR__ . '/../example/index.php';
    }

    /**
     * This is stripped down barebone version for our example/ endpoints.
     */
    protected function doRequest(string $uri, array $parameters = []): self
    {
        \Phalcon\Di::reset();
        \Phalcon\Di::setDefault($this->app->getDI());

        $parameters['_url'] = $uri;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = http_build_query($parameters);
        $_SERVER['REQUEST_URI']    = '/?' . $_SERVER['QUERY_STRING'];
        $_GET                      = $parameters;

        $this->response = null;

        ob_start();
        $this->app->handle($uri);
        $content = ob_get_clean();

        $response = $this->app->getDI()->getShared('response');

        if (empty($response->getContent())) {
            $response->setContent($content);
        }

        $this->response = $response;

        return $this;
    }

    protected function configure(array $config): self
    {
        $this->app->getDI()->getShared('config')->merge($config);

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

    protected function assertHeaderKeys(array $keys): self
    {
        $headers = $this->response->getHeaders();

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $headers);
        }

        return $this;
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
