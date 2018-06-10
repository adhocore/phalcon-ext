<?php

namespace PhalconExt\Http\Middleware;

use PhalconExt\Cache\Redis;
use PhalconExt\Di\ProvidesDi;
use Phalcon\Events\Event;
use Phalcon\Http\Request;
use Phalcon\Mvc\View;
use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Mvc\Micro as MicroApplication;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class Throttle implements MiddlewareInterface
{
    use ProvidesDi;

    /** @var string */
    protected $redis;

    /** @var array */
    protected $config;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function beforeExecuteRoute(Event $event, DispatcherInterface $dispatcher, $data = null)
    {
        return $this->handle();
    }

    /**
     * @param MicroApplication $app
     *
     * @return bool
     */
    public function call(MicroApplication $app): bool
    {
        return $this->handle();
    }

    /**
     * Common handler for both micro and mvc app.
     *
     * @return bool
     */
    protected function handle(): bool
    {
        $request  = $this->di('request');
        $retryKey = null;

        $this->config = $this->di('config')->toArray()['throttle'];

        if (null === $retryKey = $this->findRetryKey($request)) {
            return true;
        }

        if ($this->di('view') instanceof View) {
            $this->di('view')->disable();
        }

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
