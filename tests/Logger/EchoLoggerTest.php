<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Logger;

use Phalcon\Logger;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use PhalconExt\Logger\EchoLogger;
use PhalconExt\Test\TestCase;

class EchoLoggerTest extends TestCase
{
    protected $logger;

    public function setUp()
    {
        $this->logger = new EchoLogger(['level' => Logger::INFO]);

        $this->assertInstanceOf(LineFormatter::class, $this->logger->getFormatter());
    }

    public function test_log()
    {
        ob_start();

        $this->logger->log('info level msg', Logger::INFO);
        $this->logger->log('debug level msg', Logger::DEBUG);
        $this->logger->log('error level msg', Logger::ERROR);

        $buffer = ob_get_clean();

        $this->assertContains('info level msg', $buffer);
        $this->assertContains('error level msg', $buffer);
        $this->assertNotContains('debug level msg', $buffer);
    }

    public function test_close()
    {
        $this->assertTrue($this->logger->close());
    }
}
