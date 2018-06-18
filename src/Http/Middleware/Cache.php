<?php

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\BaseMiddleware;

/**
 * Cache middleware that caches request output for fast performance.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Cache extends BaseMiddleware
{
    /** @var string */
    protected $cacheKey  = '';

    protected $configKey = 'httpCache';

    protected $willCache = false;

    /**
     * Get the recent cache key used.
     *
     * @return string
     */
    public function getLastKey(): string
    {
        return $this->cacheKey;
    }

    /**
     * Handle the cache.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function before(Request $request, Response $response): bool
    {
        if (!$this->isCacheable($request, $response)) {
            return true;
        }

        $this->cacheKey = $this->getCacheKey($request);

        if (!$this->hasCache($this->cacheKey)) {
            return $this->willCache = true;
        }

        return $this->serve($response);
    }

    /**
     * Check if the output for current request is cachaeble.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    protected function isCacheable(Request $request, Response $response): bool
    {
        if (!$request->isGet()) {
            return false;
        }

        list($routeName, $url) = $this->getRouteNameUri();

        $allowedRoutes = \array_fill_keys($this->config['routes'], true);

        if (!isset($allowedRoutes[$routeName]) && !isset($allowedRoutes[$url])) {
            return false;
        }

        $statusCode = $response->getStatusCode();

        return \in_array($statusCode, [200, 204, 301, null]); // null doesnt indicate failure!
    }

    /**
     * Checks if there is cache for key corresponding to current request.
     *
     * @param string $cacheKey
     *
     * @return bool
     */
    protected function hasCache(string $cacheKey): bool
    {
        return $this->di('redis')->exists($cacheKey);
    }

    /**
     * Get cacheKey for current request.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getCacheKey(Request $request): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        $query = $request->getQuery();
        \sort($query);

        return $this->cacheKey = \md5($request->getUri() . '?' . \http_build_query($query));
    }

    /**
     * Output the cached response with correct header.
     *
     * @param Response $response
     *
     * @return bool
     */
    protected function serve(Response $response): bool
    {
        $cached = \json_decode($this->di('redis')->get($this->cacheKey));

        foreach ($cached->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        $response->setContent($cached->content)->send();

        return false;
    }

    /**
     * Write the just sent response to cache.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function after(Request $request, Response $response): bool
    {
        if (!$this->willCache) {
            return true;
        }

        $headers = ['X-Cache' => \time(), 'X-Cache-ID' => $this->cacheKey];

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

    /**
     * Get the content string.
     *
     * @param Response $response
     *
     * @return string
     */
    protected function getContent(Response $response): string
    {
        if (null !== $response->getContent()) {
            return $response->getContent();
        }

        if ($this->isMicro()) {
            return (string) $this->di('application')->getReturnedValue();
        }

        $value = $this->di('dispatcher')->getReturnedValue();

        if (\method_exists($value, 'getContent')) {
            return $value->getContent();
        }

        return (string) $value;
    }
}
