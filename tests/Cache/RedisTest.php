<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Cache;

use PhalconExt\Cache\Redis;
use PhalconExt\Test\TestCase;
use Redis as PhpRedis;

class RedisTest extends TestCase
{
    public function test_get_connection()
    {
        $r = new Redis(new \Phalcon\Cache\Frontend\None);

        $this->assertInstanceof(PhpRedis::class, $r->getConnection());
    }
}
