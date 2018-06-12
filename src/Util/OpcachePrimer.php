<?php

namespace PhalconExt\Util;

/**
 * Prime the opcache - so requests are fast from the first ever hit.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
class OpcachePrimer
{
    /**
     * Prime/Warm the cache for given paths.
     *
     * @param array $paths
     *
     * @return int The count of files whose opcache primed/warmed.
     */
    public function prime(array $paths): int
    {
        if (!\function_exists('opcache_compile_file')) {
            return 0;
        }

        $cached = 0;

        foreach ($paths as $path) {
            if (false === $path = \realpath($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($this->filter($iterator) as $file) {
                \opcache_compile_file($file->getRealPath()) && $cached++;
            }
        }

        return $cached;
    }

    /**
     * Filter php files.
     *
     * @param \RecursiveIteratorIterator $iterator
     *
     * @return \FilterIterator
     */
    protected function filter(\RecursiveIteratorIterator $iterator): \FilterIterator
    {
        return new class($iterator) extends \FilterIterator {
            public function accept(): bool
            {
                return $this->getInnerIterator()->current()->getExtension() === 'php';
            }
        };
    }
}
