<?php

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use PhalconExt\Http\BaseMiddleware;

/**
 * A request throttling middleware.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class Throttle extends BaseMiddleware
{
    /** @var string */
    protected $redis;

    protected $configKey = 'throttle';

    protected $retryKey = '';

    /**
     * Get retry key that causes throttling.
     *
     * @return string
     */
    public function getRetryKey(): string
    {
        return $this->retryKey;
    }

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
        if ('' === $this->retryKey = $this->findRetryKey($request)) {
            return true;
        }

        return $this->abort(429, 'Too many requests. Try again later.', [
            'Retry-After' => \ceil($this->di('redis')->getTtl($this->retryKey) / 60),
        ]);
    }

    /**
     * Find the redis key that contains hits counter which has exceeded threshold for throttle.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function findRetryKey(Request $request): string
    {
        $retryKey = '';
        $redis    = $this->di('redis');
        $baseKey  = $this->getKey($request);

        foreach ($this->config['maxHits'] as $minutes => $maxHits) {
            $key = "$baseKey:$minutes";

            if ($this->shouldThrottle($redis, $key, $minutes, $maxHits)) {
                $retryKey = $key;
            }
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
            $key .= ':' . \md5($request->getUserAgent());
        }

        return ($this->config['prefix'] ?? '') . $key;
    }

    /**
     * Check if we should throttle. Update hits counter if not.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function shouldThrottle($redis, string $key, int $minutes, int $maxHits): bool
    {
        $hits = $redis->get($key) ?: 0;
        $ttl  = $hits ? $redis->getTtl($key) : $minutes * 60;

        if ($hits >= $maxHits) {
            return true;
        }

        $redis->save($key, $hits + 1, $ttl);

        return false;
    }
}
