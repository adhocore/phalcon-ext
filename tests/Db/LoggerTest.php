<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Db;

use Phalcon\Events\Manager;
use PhalconExt\Db\Logger;
use PhalconExt\Db\Sqlite;
use PhalconExt\Test\TestCase;

class LoggerTest extends TestCase
{
    protected static $db;
    protected static $log;

    public static function setUpBeforeClass()
    {
        self::$db = new Sqlite([
            'dbname' => __DIR__ . '/../../example/.var/db.db',
        ]);

        $evm = new Manager;
        $evm->attach('db', new Logger([
            'enabled'        => true,
            'logPath'        => __DIR__ . '/../../example/.var/sql/',
            'addHeader'      => true,
            'backtraceLevel' => 5,
            'skipFirst'      => 2,
        ]));

        $evm->attach('db', new Logger([
            'enabled' => true,
            'logPath' => __DIR__ . '/../../example/.var/sql/',
        ]));

        $evm->attach('db', new Logger(['enabled' => false]));

        self::$db->setEventsManager($evm);
        self::$log = __DIR__ . '/../../example/.var/sql/' . date('Y-m-d') . '.sql';
    }

    public function setUp()
    {
        self::$db->execute('DELETE FROM tests');
        file_put_contents(self::$log, '');
    }

    public function test_all()
    {
        self::$db->query('SELECT 1 FROM tests');
        self::$db->query('SELECT 1 FROM tests WHERE 1 = ?', [1]);
        self::$db->query('SELECT 1 FROM tests WHERE 1 = :one', ['one' => 1]);

        $logs = file_get_contents(self::$log);

        $this->assertContains("SELECT 1 FROM tests;\n", $logs);
        $this->assertContains("SELECT 1 FROM tests WHERE 1 = '1';\n", $logs);
        $this->assertContains("SELECT 1 FROM tests WHERE 1 = '1';\n", $logs);
    }
}
