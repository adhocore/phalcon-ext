<?php

namespace PhalconExt\Test\Util;

use PhalconExt\Util\OpcachePrimer;
use PhalconExt\Test\TestCase;

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
