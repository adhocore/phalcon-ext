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

use PhalconExt\Db\Sqlite;
use PhalconExt\Test\TestCase;

class ExtensionTest extends TestCase
{
    protected static $db;

    public static function setUpBeforeClass()
    {
        self::$db = new Sqlite([
            'dbname' => __DIR__ . '/../../example/.var/db.db',
        ]);
    }

    public function setUp()
    {
        self::$db->execute('DELETE FROM tests');
    }

    public function test_upsert_countBy()
    {
        $this->assertFalse(self::$db->upsert('tests', [], []));

        $this->assertEmpty(self::$db->countBy('tests', ['prop_a' => 'A1']));

        $this->assertTrue(self::$db->upsert('tests', ['prop_b' => 'B1'], ['prop_a' => 'A1']));

        $this->assertSame(1, self::$db->countBy('tests', ['prop_a' => 'A1']));

        $this->assertTrue(self::$db->upsert('tests', ['prop_c' => 'C1'], ['prop_a' => 'A1']));

        $this->assertSame(1, self::$db->countBy('tests', ['prop_a' => 'A1', 'prop_c' => 'C1']));

        $this->assertSame(1, self::$db->countBy('tests', []));

        static::$db->insertAsDict('tests', ['prop_a' => 'A1']);

        $this->expectException(\InvalidArgumentException::class);
        $this->assertTrue(self::$db->upsert('tests', ['prop_b' => 'B1'], ['prop_a' => 'A1']));
    }

    public function test_clause_binds()
    {
        list($clause, $binds) = self::$db->clauseBinds([]);

        $this->assertEmpty($clause);
        $this->assertEmpty($binds);

        list($clause, $binds) = self::$db->clauseBinds(['a' => 1]);

        $this->assertSame('a = ?', $clause);
        $this->assertSame([1], $binds);

        list($clause, $binds) = self::$db->clauseBinds(['a' => 1, 'b' => 2], true);

        $this->assertSame('a = :a AND b = :b', $clause);
        $this->assertSame(['a' => 1, 'b' => 2], $binds);
    }

    public function test_insert_bulk()
    {
        $data = [
            ['prop_a' => 1],
            ['prop_b' => 1],
            ['prop_c' => 1],
        ];

        $this->assertTrue(static::$db->insertAsBulk('tests', $data));
        $this->assertSame(count($data), self::$db->countBy('tests', []));

        $data = [
            ['prop_a' => 1, 'prop_c' => 3],
            ['prop_b' => 2, 'prop_a' => 3],
            ['prop_c' => 2, 'prop_b' => 1],
        ];

        $this->assertTrue(static::$db->insertAsBulk('tests', $data));
        $this->assertSame(6, self::$db->countBy('tests', []));
        $this->assertSame(2, self::$db->countBy('tests', ['prop_a' => 1]));
        $this->assertSame(2, self::$db->countBy('tests', ['prop_b' => 1]));
        $this->assertSame(1, self::$db->countBy('tests', ['prop_c' => 3]));
    }

    public function test_get_inclusive_columns()
    {
        $data = [
            ['a' => 1, 'c' => 3],
            ['d' => 2, 'b' => 3],
            ['c' => 2, 'e' => 1],
        ];

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], self::$db->getInclusiveColumns($data));
    }
}
