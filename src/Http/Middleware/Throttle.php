<?php

namespace PhalconExt\Http\Middleware;

use PhalconExt\Cache\Redis;
use PhalconExt\Http\BaseMiddleware;
use Phalcon\Http\Request;

class Throttle extends BaseMiddleware
{
    /** @var string */
    protected $redis;

    protected $configKey = 'throttle';

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;

        parent::__construct();
    }

    protected function handle(): bool
    {
        $retryKey     = null;
        $this->config = $this->di('config')->toArray()['throttle'];

        if (null === $retryKey = $this->findRetryKey($this->di('request'))) {
            return true;
        }

        $this->disableView();

        $after = \ceil($this->redis->getTTL($retryKey) / 60);

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
        $baseKey  = $this->getKey($request);

        foreach ($this->config['maxHits'] as $minutes => $maxHits) {
            $key  = "$baseKey:$minutes";
            $hits = $this->redis->exists($key) ? $this->redis->get($key) : 0;
            $ttl  = $hits ? $this->redis->getTTL($key) : $minutes * 60;

            if (null === $retryKey && $hits >= $maxHits) {
                $retryKey = $key;

                continue;
            }

            $this->redis->save($key, $hits + 1, $ttl);
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
