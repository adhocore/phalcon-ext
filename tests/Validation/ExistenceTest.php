<?php

namespace PhalconExt\Test\Validation;

use PhalconExt\Validation\Existence;
use PhalconExt\Test\WebTestCase;

class ExistenceTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->di('db')->execute('CREATE TABLE IF NOT EXISTS tests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            prop_a VARCHAR(25),
            prop_b VARCHAR(255),
            prop_c VARCHAR(10)
        )');

        $this->di('db')->execute('DELETE FROM tests');
        $this->di('db')->execute('DELETE FROM SQLITE_SEQUENCE WHERE name = "tests"');
    }

    public function test_validate_without_options()
    {
        $rules = ['tests' => 'required|exist'];
        $vldtr = $this->di('validation')->run($rules, ['tests' => 1]);

        $this->assertFalse($vldtr->pass());

        $this->di('db')->insertAsDict('tests', ['prop_a' => 'A']);
        $vldtr = $this->di('validation')->run($rules, ['tests' => 1]);

        $this->assertTrue($vldtr->pass());
    }

    public function test_validate_with_table()
    {
        $rules = ['prop_a' => 'required|exist:table:tests'];

        $this->di('db')->insertAsDict('tests', ['prop_a' => 'A']);
        $vldtr = $this->di('validation')->run($rules, ['prop_a' => 'A']);

        $this->assertTrue($vldtr->pass());
    }

    public function test_validate_with_table_column()
    {
        $rules = ['the_b' => 'required|exist:table:tests;column:prop_b'];

        $this->di('db')->insertAsDict('tests', ['prop_b' => 'B']);
        $vldtr = $this->di('validation')->run($rules, ['the_b' => 'B']);

        $this->assertTrue($vldtr->pass());
    }
}
