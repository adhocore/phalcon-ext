<?php

namespace PhalconExt\Http\Middleware;

use PhalconExt\Http\BaseMiddleware;
use Phalcon\Http\Request;

class Cache extends BaseMiddleware
{
    /** @var string */
    protected $cacheKey;

    protected $configKey = 'requestCache';

    public function afterExecuteRoute()
    {
        $this->cache();
    }

    protected function handle(): bool
    {
        $this->cacheKey = $this->getCacheKey($this->di('request'));

        $cacheable = $this->isCacheable();
        if (!$cacheable || !$this->hasCache()) {
            return $cacheable ? $this->willCache() : true;
        }

        return $this->serve();
    }

    protected function isCacheable(): bool
    {
        return $this->di('request')->isGet();
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

    protected function getCacheKey(Request $request)
    {
        if ($this->cacheKey) {
            return $cacheKey;
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

    public function cache()
    {
        $response = $this->di('response');
        $headers  = ['X-Cache' => time(), 'X-Cache-ID' => $this->cacheKey];

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

    protected function getContent($response)
    {
        if (null !== $response->getContent()) {
            return $response->getContent();
        }

        if ($this->di()->has('application')) {
            return (string) $this->di('application')->getReturnedValue();
        }

        return '';
    }
}
