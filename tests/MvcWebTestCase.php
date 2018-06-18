<?php

namespace PhalconExt\Test;

class MvcWebTestCase extends WebTestCase
{
    public function setUp()
    {
        // A new instance of fully configured app :)
        $this->app = include __DIR__ . '/../example/mvc.php';

        $this->resetDi();
    }
}
