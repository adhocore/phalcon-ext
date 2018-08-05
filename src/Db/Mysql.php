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

use Phalcon\Db\Adapter\Pdo\Mysql as BaseMysql;

class Mysql extends BaseMysql
{
    use Extension;
}
