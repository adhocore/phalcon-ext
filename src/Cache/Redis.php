<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Cache;

use Phalcon\Cache\Adapter\Redis as BaseRedis;
use Redis as PhpRedis;

/**
 * Redis backend client.
 */
class Redis extends BaseRedis
{
    /**
     * Get underlying redis connection.
     *
     * @return null|PhpRedis
     */
    public function getConnection()
    {
        if (!$this->_redis) {
            $this->_connect();
        }

        return $this->_redis;
    }

    /**
     * Get the remaining ttl for given key.
     *
     * @param string $key
     *
     * @return int
     */
    public function getTtl($key): int
    {
        return $this->getConnection()->ttl("_PHCR$key") ?: -1;
    }
}
