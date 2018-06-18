<?php

namespace PhalconExt\Test\Util;

use PhalconExt\Test\TestCase;
use PhalconExt\Util\OpcachePrimer;

class OpcachePrimerTest extends TestCase
{
    public function setUp()
    {
        if (!extension_loaded('Zend Opcache') || !ini_get('opcache.enable_cli')) {
            $this->markTestSkipped('Zend Opcache required');
        }

        $this->primer = new OpcachePrimer;
    }

    public function test_prime()
    {
        $expected = count(glob(__DIR__ . '/stub/*.php'));
        $actual   = $this->primer->prime([__DIR__ . '/stub']);

        $this->assertSame($expected, $actual);
    }
}
