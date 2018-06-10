<?php

namespace PhalconExt\Di;

use Phalcon\Di;

/**
 * Provides di service.
 */
trait ProvidesDi
{
    public function di(string $service = null, array $parameters = [])
    {
        if (null === $service) {
            return Di::getDefault();
        }

        return Di::getDefault()->resolve($service, $parameters);
    }
}
