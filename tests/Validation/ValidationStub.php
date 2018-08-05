<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Test\Validation;

class ToArray
{
    protected $apple = 'A';

    public function toArray()
    {
        return get_object_vars($this);
    }
}

class GetData
{
    public function getData()
    {
        return ['ball' => 'ABCDEF'];
    }
}

class Entity
{
    //
}
