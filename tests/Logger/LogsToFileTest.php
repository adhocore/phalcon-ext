<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Logger;

use PhalconExt\Logger\LogsToFile;
use PhalconExt\Test\TestCase;

class LogsToFileTest extends TestCase
{
    use LogsToFile;

    protected $fileExtension = '.txt';

    public function test_log()
    {
        $this->assertEmpty($this->log('Wont be logged'));

        $this->activate(__DIR__);
        $this->log('Would be logged');

        $this->assertFileExists(__DIR__ . '/' . date('Y-m-d') . $this->fileExtension);

        $logs = file_get_contents(__DIR__ . '/' . date('Y-m-d') . $this->fileExtension);

        $this->assertContains('Would be logged', $logs);
        $this->assertNotContains('Wont be logged', $logs);
    }

    public function tearDown()
    {
        unlink(__DIR__ . '/' . date('Y-m-d') . $this->fileExtension);
    }
}
