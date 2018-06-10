<?php

namespace PhalconExt\Cache;

use Phalcon\Cache\Backend\Redis as BaseRedis;

class Redis extends BaseRedis
{
    public function getConnection()
    {
        return $this->_redis;
    }

    public function getTTL(string $key): int
    {
        return $this->_redis->ttl("_PHCR$key") ?: -1;
    }
}
