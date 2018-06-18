<?php

namespace PhalconExt\Test\Di;

use Phalcon\Db\Adapter;
use Phalcon\Http\Response;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Test\WebTestCase;

class ExtensionTest extends WebTestCase
{
    protected $di;

    public function setUp()
    {
        parent::setUp();

        $this->di = $this->app->getDI();
    }

    public function test_resolve()
    {
        $this->di->registerAliases(['httpResponse' => 'response', Adapter::class => 'db']);

        $this->assertInstanceOf(Response::class, $this->di->resolve('response'), 'resolve by known name');
        $this->assertInstanceOf(Response::class, $this->di->resolve(Response::class), 'resolve by auto alias');
        $this->assertInstanceOf(Response::class, $this->di->resolve('httpResponse'), 'resolve by custom alias');

        $this->assertInstanceOf(NeedsDb::class, $this->di->resolve(NeedsDb::class), 'resolve by fqcn');
        $this->assertInstanceOf(DeepNest::class, $this->di->resolve(DeepNest::class), 'resolve deep nested');
        $this->assertInstanceOf(NeedsSqlite::class, $this->di->resolve(NeedsSqlite::class), 'resolve with name');
        $this->assertInstanceOf(NeedsApple::class, $this->di->resolve(NeedsApple::class, ['apple' => 1]), 'resolve with params');
        $this->assertInstanceOf(NeedsNothing::class, $this->di->resolve(NeedsNothing::class), 'resolve without params');
        $this->assertInstanceOf(NeedsNullable::class, $this->di->resolve(NeedsNullable::class), 'resolve without params');
        $this->assertInstanceOf(HasDefaults::class, $this->di->resolve(HasDefaults::class), 'resolve without params');
    }

    public function test_cyclic_deps()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cyclic dependency for class:');

        $this->di->resolve(One::class);
    }

    public function test_uninstantiable()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot instantiate class:');

        $this->di->resolve(ProvidesDi::class);
    }

    public function test_unresolvable()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dependency:');

        $this->di->resolve(Three::class);
    }

    public function test_replace_restore()
    {
        $dbMock            = new class {
            public $mocked = true;
        };

        $this->di->replace(['db' => $dbMock]);

        $this->assertNotInstanceOf(Adapter::class, $this->di->get('db'));
        $this->assertTrue($this->di->get('db')->mocked);

        $this->di->restore(['db']);

        $this->assertInstanceOf(Adapter::class, $this->di->get('db'));
    }
}
