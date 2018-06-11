<?php

namespace PhalconExt\Util;

class OpcachePrimer
{
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
