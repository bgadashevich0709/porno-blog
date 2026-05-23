<?php

namespace App\Common\Container;

use Exception;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    private array $definitions = [];
    private array $instances = [];

    public function set(string $id, mixed $definition): void
    {
        $this->definitions[$id] = $definition;
        unset($this->instances[$id]);
    }

    public function setContextual(string $targetClass, string $parameterName, mixed $definition): void
    {
        $contextualKey = "context:{$targetClass}:{$parameterName}";
        $this->definitions[$contextualKey] = $definition;
    }

    /**
     * @throws Exception
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            if ($definition instanceof \Closure) {
                return $this->instances[$id] = $definition($this);
            }

            if (is_string($definition) && class_exists($definition)) {
                return $this->instances[$id] = $this->autowire($definition);
            }

            return $this->instances[$id] = $definition;
        }

        if (class_exists($id)) {
            return $this->instances[$id] = $this->autowire($id);
        }

        if (interface_exists($id)) {
            foreach ($this->definitions as $definedId => $definition) {
                if (class_exists($definedId)) {
                    $ref = new ReflectionClass($definedId);
                    if ($ref->implementsInterface($id)) {
                        return $this->instances[$id] = $this->get($definedId);
                    }
                }
            }

            $withoutInterface = str_replace('Interface', '', $id);
            if (class_exists($withoutInterface)) {
                $ref = new ReflectionClass($withoutInterface);
                if ($ref->implementsInterface($id)) {
                    return $this->instances[$id] = $this->autowire($withoutInterface);
                }
            }

            $pathParts = explode('\\', $id);
            $namespace = implode('\\', array_slice($pathParts, 0, -1));

            $declaredClasses = get_declared_classes();
            $implementations = [];

            foreach ($declaredClasses as $className) {
                if (!str_starts_with($className, $namespace)) {
                    continue;
                }

                $reflection = new ReflectionClass($className);
                if ($reflection->isInstantiable() && $reflection->implementsInterface($id)) {
                    $implementations[] = $className;
                }
            }

            if (count($implementations) === 1) {
                return $this->instances[$id] = $this->autowire($implementations[0]);
            }

            if (count($implementations) > 1) {
                throw new Exception(
                    "Interface '{$id}' has multiple implementations: [" . implode(', ', $implementations) . "]. " .
                    "Please configure contextual binding explicitly using setContextual()."
                );
            }
        }

        throw new Exception("Service, Interface or Class '{$id}' cannot be resolved.");
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || isset($this->instances[$id])
            || class_exists($id)
            || interface_exists($id);
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    private function autowire(string $class): mixed
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class '{$class}' is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $contextualKey = "context:{$class}:{$paramName}";

            if (isset($this->definitions[$contextualKey])) {
                $definition = $this->definitions[$contextualKey];
                $dependencies[] = $definition instanceof \Closure ? $definition($this) : $definition;
                continue;
            }

            $type = $parameter->getType();

            if ($type === null) {
                throw new Exception("Cannot resolve parameter '{$paramName}' in '{$class}': Missing type-hint.");
            }

            if ($type->isBuiltin()) {
                throw new Exception("Cannot autowire built-in parameter '{$paramName}' in '{$class}'.");
            }

            // Защита типов для старых версий PHP
            $dependencies[] = $this->get($type instanceof \ReflectionNamedType ? $type->getName() : (string) $type);
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
