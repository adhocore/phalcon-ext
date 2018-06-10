<?php

namespace PhalconExt\Di;

/**
 * An extension to phalcon di.
 */
trait Extension
{
    /** @var array Names of services currently resolving */
    protected $resolving = [];

    /** @var array Original services backup */
    protected $original  = [];

    /** @var array The aliases of services */
    protected $aliases   = [];

    /**
     * Resolve all the dependencies for a class FQCN and instantiate it.
     *
     * @param  string $class      FQCN
     * @param  array  $parameters Parameters
     *
     * @return object
     */
    public function resolve(string $class, array $parameters = [])
    {
        if ($this->has($class)) {
            return $this->get($class);
        }

        if ($this->aliases[$class] ?? null) {
            return $this->get($this->aliases[$class]);
        }

        if ($this->resolving[$class] ?? null) {
            throw new \RuntimeException('Cyclic dependency for class: ' . $class);
        }

        // 1. Try if we can get it normally from \Phalcon\Di.
        // 2. Resolve with constructor injection of dependencies from \Phalcon\Di.
        try {
            $resolved = $this->get($class, $parameters);
        } catch (\Throwable $e) {
            $this->resolving[$class] = true;
            $resolved = $this->instantiate($class, $parameters);
        }

        $this->setShared($class, $resolved);

        return $resolved;
    }

    /**
     * Instantiate class FQCN by constructor injecting the dependencies from the DI.
     *
     * @param  string $class      FQCN
     * @param  array  $parameters Parameters
     *
     * @return object
     */
    protected function instantiate(string $class, array $parameters = [])
    {
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException('Cannot instantiate class: ' . $class);
        }

        if ([] === $dependencies = $reflector->getConstructor()->getParameters()) {
            return $reflector->newInstance();
        }

        return $reflector->newInstanceArgs(
            $this->resolveDependencies($dependencies, $parameters)
        );
    }

    protected function resolveDependencies(array $dependencies, array $parameters)
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->name;

            // Already available in parameters.
            if (isset($parameters[$name])) {
                $resolved[] = $parameters[$name];
            }
            // Already registered in DI.
            elseif ($this->has($name)) {
                $resolved[] = $this->getShared($name);
            }
            // Is a class and needs to be resolved.
            elseif ($subClass = $dependency->getClass()) {
                $resolved[] = $this->resolve($subClass->name);
            }
            // Use default value.
            elseif ($dependency->isOptional()) {
                $resolved[] = $dependency->getDefaultValue();
            }
            else {
                throw new \RuntimeException('Cannot resolve dependency $' . $name);
            }
        }

        return $resolved;
    }

    public function replace(array $services)
    {
        foreach ($services as $name => $definition) {
            if ($this->has($name)) {
                $this->original[$name] = $this->get($name);
                $this->remove($name);
            }

            $this->set($name, $definition);
        }

        return $this;
    }

    public function restore(string $name = null)
    {
        if ($name && empty($this->original[$name])) {
            return $this;
        }

        $names = $name ? [$name] : array_keys($this->original);

        foreach ($names as $name) {
            if (!$this->has($name)) {
                continue;
            }

            $this->remove($name);
            $this->setShared($name, $this->original[$name]);

            unset($this->original[$name]);
        }

        return $this;
    }

    public function registerAliases(array $aliases = [])
    {
        $this->aliases += $aliases;

        foreach ($this->_services as $name => $service) {
            $def   = $service->getDefinition();
            $isStr = \is_string($def);

            if ($isStr || (\is_object($def) && !$def instanceof \Closure)) {
                $alias = $isStr ? $def : \get_class($def);
                $this->aliases[$alias] = $name;
            }
        }

        return $this;
    }
}
