<?php

namespace PhalconExt\Db;

use Phalcon\Db\Adapter\Pdo\Postgresql as BasePostgresql;

class Postgresql extends BasePostgresql
{
    use Extension;
}
