<?php

namespace PhalconExt\Test\Validation;

use PhalconExt\Test\WebTestCase;

class ExistenceTest extends WebTestCase
{
    public function test_validate_without_options()
    {
        $rules = ['tests' => 'required|exist'];
        $vldtr = $this->di('validation')->run($rules, ['tests' => 0]);

        $this->assertFalse($vldtr->pass());

        $this->di('db')->insertAsDict('tests', ['prop_a' => 'A']);
        $vldtr = $this->di('validation')->run($rules, ['tests' => $this->di('db')->lastInsertId()]);

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
