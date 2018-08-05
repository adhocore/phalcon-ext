<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

putenv('APP_ENV=test');

// @see http://php.net/manual/en/function.class-uses.php#110752
function class_uses_deep($class, $autoload = true): array
{
    $traits = [];

    do {
        $traits = array_merge(class_uses($class, $autoload), $traits);
    } while ($class = get_parent_class($class));

    foreach ($traits as $trait => $same) {
        $traits = array_merge(class_uses($trait, $autoload), $traits);
    }

    return array_unique($traits);
}

require_once __DIR__ . '/../example/setup.php';
