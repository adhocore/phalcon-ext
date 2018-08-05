<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Db;

use Phalcon\Db\Adapter\Pdo\Postgresql as BasePostgresql;

class Postgresql extends BasePostgresql
{
    use Extension;
}
