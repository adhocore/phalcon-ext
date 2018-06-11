<?php

namespace PhalconExt\Http\Middleware;

use Phalcon\Http\Request;
use PhalconExt\Http\BaseMiddleware;

class Throttle extends BaseMiddleware
{
    /** @var string */
    protected $redis;

    protected $configKey = 'throttle';

    protected function handle(): bool
    {
        $retryKey     = null;
        $this->config = $this->di('config')->toArray()['throttle'];

        if (null === $retryKey = $this->findRetryKey($this->di('request'))) {
            return true;
        }

        $this->disableView();

        $after = \ceil($this->di('redis')->getTtl($retryKey) / 60);

        $this->di('response')
            ->setContent("Too many requests. Try again in $after min.")
            ->setHeader('Retry-After', $after)
            ->setStatusCode(429)
            ->send();

        return false;
    }

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

    protected function getKey(Request $request): string
    {
        $key = $request->getClientAddress(true);

        if ($this->config['checkUserAgent'] ?? false) {
            $key .= '_' . \md5($request->getUserAgent());
        }

        return ($this->config['prefix'] ?? '') . $key;
    }
}
