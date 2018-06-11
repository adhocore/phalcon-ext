<?php

namespace PhalconExt\Http\Middleware;

use PhalconExt\Http\BaseMiddleware;
use Phalcon\Http\Request;
use Phalcon\Http\Response;

class Cache extends BaseMiddleware
{
    /** @var string */
    protected $cacheKey;

    protected $configKey = 'httpCache';

    public function afterExecuteRoute(): bool
    {
        return $this->cache();
    }

    protected function handle(): bool
    {
        if (false === $this->isCacheable()) {
            return true;
        }

        $this->cacheKey = $this->getCacheKey($this->di('request'));

        if (!$this->hasCache()) {
            return $this->willCache();
        }

        return $this->serve();
    }

    protected function isCacheable(): bool
    {
        if (false === $this->di('request')->isGet()) {
            return false;
        }

        list($routeName, $url) = $this->getRouteNameUrl();

        $allowedRoutes = \array_fill_keys($this->config['routes'], true);

        if (!isset($allowedRoutes[$routeName]) && !isset($allowedRoutes[$url])) {
            return false;
        }

        $statusCode = $this->di('response')->getStatusCode();

        return \in_array($statusCode, [200, 204, 301, null]); // null doesnt indicate failure!
    }

    protected function hasCache(): bool
    {
        return $this->di('redis')->exists($this->cacheKey);
    }

    protected function willCache(): bool
    {
        if ($this->isMicro()) {
            $this->di('application')->after([$this, 'cache']);

            return true;
        }

        $this->di('eventsManager')->attach('dispatch:afterExecuteRoute', $this);

        return true;
    }

    protected function getCacheKey(Request $request): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        $query = $request->getQuery();
        \sort($query);

        return $this->cacheKey = \md5($request->getUri() . '?' . \http_build_query($query));
    }

    protected function serve(): bool
    {
        $response = $this->di('response');
        $cached   = \json_decode($this->di('redis')->get($this->cacheKey));

        foreach ($cached->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        $response->setContent($cached->content)->send();

        return false;
    }

    public function cache(): bool
    {
        $response = $this->di('response');
        $headers  = ['X-Cache' => \time(), 'X-Cache-ID' => $this->cacheKey];

        foreach ($response->getHeaders()->toArray() as $key => $value) {
            if (\strpos($key, 'Access-Control-') === false) {
                $headers[$key] = $value;
            }
        }

        $this->di('redis')->save($this->cacheKey, \json_encode([
            'headers' => $headers,
            'content' => $this->getContent($response),
        ]), $this->config['ttl'] * 60);

        return true;
    }

    protected function getContent(Response $response): string
    {
        if (null !== $response->getContent()) {
            return $response->getContent();
        }

        if ($this->isMicro()) {
            return (string) $this->di('application')->getReturnedValue();
        }

        return (string) $this->di('dispatcher')->getReturnedValue();
    }
}
