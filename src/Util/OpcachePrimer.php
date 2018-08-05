<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Util;

/**
 * Prime the opcache - so requests are fast from the first ever hit.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 *
 * @codeCoverageIgnore Only feasible based on opcache availability &/or setting. Shouldnt affect coverage :/.
 */
class OpcachePrimer
{
    public function __construct()
    {
        // @codeCoverageIgnoreStart
        if (!\function_exists('opcache_compile_file')) {
            throw new \Exception('Opcache is not enabled');
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Prime/Warm the cache for given paths.
     *
     * @param array $paths
     *
     * @return int The count of files whose opcache primed/warmed.
     */
    public function prime(array $paths): int
    {
        $cached = 0;

        foreach ($this->normalizePaths($paths) as $path) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($this->filter($iterator) as $file) {
                $cached += (int) \opcache_compile_file($file->getRealPath());
            }
        }

        return $cached;
    }

    /**
     * Normalize paths.
     *
     * @param array $paths
     *
     * @return array
     */
    protected function normalizePaths(array $paths): array
    {
        return \array_filter(\array_map('realpath', $paths));
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
