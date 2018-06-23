<?php

namespace PhalconExt\Di;

/**
 * An extension to phalcon di.
 *
 * @author  Jitendra Adhikari <jiten.adhikary@gmail.com>
 * @license MIT
 *
 * @link    https://github.com/adhocore/phalcon-ext
 */
trait Extension
{
    /** @var array Names of services currently resolving */
    protected $resolving = [];

    /** @var array Original services backup */
    protected $original  = [];

    /** @var array The aliases of services */
    protected $aliases   = [];

    // Implemented by \Phalcon\Di.
    abstract public function set($service, $definition, $shared = false);

    abstract public function get($service, $parameters = null);

    abstract public function has($service);

    abstract public function remove($service);

    /**
     * Resolve all the dependencies for a class FQCN and instantiate it.
     *
     * @param string $class      FQCN
     * @param array  $parameters Parameters
     *
     * @return object
     */
    public function resolve(string $class, array $parameters = [])
    {
        if ($this->has($class)) {
            return $this->get($class, $parameters);
        }

        if ($this->aliases[$class] ?? null) {
            return $this->get($this->aliases[$class]);
        }

        if ($this->resolving[$class] ?? null) {
            throw new \RuntimeException('Cyclic dependency for class: ' . $class);
        }

        $this->resolving[$class] = true;
        $this->set($class, $resolved = $this->instantiate($class, $parameters), true);
        unset($this->resolving[$class]);

        return $resolved;
    }

    /**
     * Instantiate class FQCN by constructor injecting the dependencies from the DI.
     *
     * @param string $class      FQCN
     * @param array  $parameters Parameters
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

    /**
     * Resolve dependencies of a class.
     *
     * @param array $dependencies
     * @param array $parameters
     *
     * @return mixed
     */
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
                $resolved[] = $this->get($name);
            } else {
                $resolved[] = $this->resolveDependency($dependency);
            }
        }

        return $resolved;
    }

    /**
     * Resolve a dependency.
     *
     * @param \ReflectionParameter $dependency
     *
     * @return mixed
     */
    protected function resolveDependency(\ReflectionParameter $dependency)
    {
        $allowsNull = $dependency->allowsNull();

        // Is a class and needs to be resolved OR is nullable.
        if ($subClass = $dependency->getClass()) {
            return $this->resolveSubClassOrNullable($subClass->name, $allowsNull);
        }
        if ($allowsNull) {
            return null;
        }

        // Use default value.
        if ($dependency->isOptional()) {
            return $dependency->getDefaultValue();
        }

        throw new \RuntimeException('Cannot resolve dependency: $' . $dependency->name);
    }

    /**
     * Resolve subClass or nullable.
     *
     * @param string $subClass
     * @param bool   $allowsNull
     *
     * @return mixed
     */
    protected function resolveSubClassOrNullable(string $subClass, bool $allowsNull = false)
    {
        try {
            return $this->resolve($subClass);
        } catch (\Throwable $e) {
            if (!$allowsNull) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Replace services with another one. Great for test mocks.
     *
     * @param array $services
     *
     * @return self
     */
    public function replace(array $services): self
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

    /**
     * Restore given service or all.
     *
     * @param array|null $name
     *
     * @return self
     */
    public function restore(array $names = null): self
    {
        foreach ($names ?? \array_keys($this->original) as $name) {
            if ($this->has($name)) {
                $this->remove($name);
                $this->set($name, $this->original[$name], true);
            }

            unset($this->original[$name]);
        }

        return $this;
    }

    /**
     * Register aliases for services.
     *
     * @param array $aliases
     *
     * @return self
     */
    public function registerAliases(array $aliases = []): self
    {
        $this->aliases += $aliases;

        foreach ($this->_services as $name => $service) {
            if ('' !== $alias = $this->inferAlias($service->getDefinition())) {
                $this->aliases[$alias] = $name;
            }
        }

        return $this;
    }

    /**
     * Infer alias of service definition.
     *
     * @param mixed $definition
     *
     * @return string
     */
    protected function inferAlias($definition): string
    {
        if (\is_object($definition) && !$definition instanceof \Closure) {
            return \get_class($definition);
        }

        return \is_string($definition) ? $definition : '';
    }
}
