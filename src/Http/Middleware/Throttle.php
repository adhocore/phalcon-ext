<?php

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\BaseMiddleware;

class Throttle extends BaseMiddleware
{
    /** @var string */
    protected $redis;

    protected $configKey = 'throttle';

    /**
     * Handle the throttle.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    public function before(Request $request, Response $response): bool
    {
        if (null === $retryKey = $this->findRetryKey($request)) {
            return true;
        }

        $this->disableView();

        $after = \ceil($this->di('redis')->getTtl($retryKey) / 60);

        $response
            ->setContent("Too many requests. Try again in $after min.")
            ->setHeader('Retry-After', $after)
            ->setStatusCode(429)
            ->send();

        return false;
    }

    /**
     * Find the redis key that contains hits counter which has exceeded threshold for throttle.
     *
     * @param Request $request
     *
     * @return null|string
     */
    protected function findRetryKey(Request $request): ?string
    {
        $retryKey = null;
        $redis    = $this->di('redis');
        $baseKey  = $this->getKey($request);

        foreach ($this->config['maxHits'] as $minutes => $maxHits) {
            $key  = "$baseKey:$minutes";
            $hits = $redis->exists($key) ? $redis->get($key) : 0;
            $ttl  = $hits ? $redis->getTtl($key) : $minutes * 60;

            if (null === $retryKey && $hits >= $maxHits) {
                $retryKey = $key;

                continue;
            }

            $redis->save($key, $hits + 1, $ttl);
        }

        return $retryKey;
    }

    /**
     * Get the unique key for this client.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getKey(Request $request): string
    {
        $key = $request->getClientAddress(true);

        if ($this->config['checkUserAgent'] ?? false) {
            $key .= '_' . \md5($request->getUserAgent());
        }

        return ($this->config['prefix'] ?? '') . $key;
    }
}
