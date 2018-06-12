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
    protected $cacheKey;

    protected $configKey = 'httpCache';

    /**
     * After route executed in mvc.
     *
     *@return bool
     */
    public function afterExecuteRoute(): bool
    {
        return $this->cache();
    }

    /**
     * Handle the cache.
     *
     *@return bool
     */
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

    /**
     * Check if the output for current request is cachaeble.
     *
     * @return bool
     */
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

    /**
     * Checks if there is cache for key corresponding to current request.
     *
     * @return bool
     */
    protected function hasCache(): bool
    {
        return $this->di('redis')->exists($this->cacheKey);
    }

    /**
     * Will cache the request after it is fulfilled and response created.
     *
     * @return bool
     */
    protected function willCache(): bool
    {
        if ($this->isMicro()) {
            $this->di('application')->after([$this, 'cache']);

            return true;
        }

        $this->di('eventsManager')->attach('dispatch:afterExecuteRoute', $this);

        return true;
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
     * @return bool
     */
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

    /**
     * Write the just sent response to cache.
     *
     * @return bool
     */
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

        return (string) $this->di('dispatcher')->getReturnedValue();
    }
}
