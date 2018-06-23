<?php

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
