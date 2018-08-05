<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Di;

use Phalcon\Db\Adapter;
use PhalconExt\Di\ProvidesDi;
use PhalconExt\Mail\Mailer;

class DiProvider
{
    use ProvidesDi;
}

class NeedsDb
{
    public function __construct(Adapter $_db)
    {
    }
}

class NeedsMailer
{
    public function __construct(Mailer $m)
    {
    }
}

class DeepNest
{
    public function __construct(NeedsDb $n, NeedsMailer $nm)
    {
    }
}

class NeedsSqlite
{
    public function __construct(Adapter\Pdo\Sqlite $db)
    {
    }
}

class NeedsApple
{
    public function __construct($apple)
    {
    }
}

class NeedsNothing
{
    public function __construct()
    {
    }
}

class NeedsNullable
{
    public function __construct(string $ball = null)
    {
    }
}

class HasDefaults
{
    public function __construct(int $size = 10)
    {
    }
}

class One
{
    public function __construct(Two $two)
    {
    }
}

class Two
{
    public function __construct(One $two)
    {
    }
}

class Three
{
    public function __construct(float $something)
    {
    }
}
