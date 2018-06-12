<?php

namespace PhalconExt\Di;

use Phalcon\Di;

/**
 * Provides di service.
 */
trait ProvidesDi
{
    /**
     * Provide the di instance or a service if its name is given.
     *
     * @param string|null $service
     * @param array       $parameters
     *
     * @return mixed
     */
    public function di(string $service = null, array $parameters = [])
    {
        if (null === $service) {
            return Di::getDefault();
        }

        return Di::getDefault()->resolve($service, $parameters);
    }
}
