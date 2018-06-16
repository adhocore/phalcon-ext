<?php

namespace PhalconExt\Test\Cache;

use Redis as PhpRedis;
use PhalconExt\Cache\Redis;
use PhalconExt\Test\TestCase;

class RedisTest extends TestCase
{
    public function test_get_connection()
    {
        $r = new Redis(new \Phalcon\Cache\Frontend\None);

        $this->assertInstanceof(PhpRedis::class, $r->getConnection());
    }
}
