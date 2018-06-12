<?php

namespace PhalconExt\Cache;

use Redis as PhpRedis;
use Phalcon\Cache\Backend\Redis as BaseRedis;

/**
 * Redis backend client.
 */
class Redis extends BaseRedis
{
    /**
     * Get underlying redis connection
     *
     * @return null|PhpRedis
     */
    public function getConnection(): ?PhpRedis
    {
        return $this->_redis;
    }

    /**
     * Get the remaining ttl for given key.
     *
     * @param string $key
     *
     * @return int
     */
    public function getTtl(string $key): int
    {
        return $this->_redis->ttl("_PHCR$key") ?: -1;
    }
}
