<?php

namespace Simply\Container;

use Simply\Container\Entry\CallableEntry;
use Simply\Container\Entry\MixedEntry;
use Simply\Container\Entry\ProviderEntry;
use Simply\Container\Entry\WiredEntry;
use Simply\Container\Exception\ContainerException;

/**
 * Class that provides convenience functionality for setting up containers.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ContainerBuilder
{
    /** @var Container The container that is being built */
    private $container;

    /**
     * ContainerBuilder constructor.
     */
    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Returns the container that is being built.
     * @return Container The container that is being built
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Registers an array with identifier value pairs to the container as mixed entries.
     * @param array $configuration The configuration array to register
     * @throws ContainerException If trying to register values that already exist
     */
    public function registerConfiguration(array $configuration): void
    {
        foreach ($configuration as $identifier => $value) {
            $this->container->addEntry($identifier, new MixedEntry($value));
        }
    }

    /**
     * Registers the given provider as a callable and applicable methods as provider methods.
     * @param EntryProvider $provider The entry provider to register
     * @throws ContainerException If the provider tries to provide something that has already been registered
     */
    public function registerProvider(EntryProvider $provider): void
    {
        $class = \get_class($provider);

        $this->container->addEntry($class, new CallableEntry([\get_class($provider), 'initialize']));

        foreach ($provider->getMethods() as $identifier => $method) {
            $this->container->addEntry($identifier, new ProviderEntry([$provider, $method]));
        }
    }

    /**
     * Registers classes that can be wired automatically based on constructor arguments.
     * @param string[] $classes List of classes to register for autowiring
     * @param string[] $overrides Override identifiers for constructor parameters
     * @throws ContainerException If trying to register classes that have already been registered
     */
    public function registerAutowiredClasses(array $classes, array $overrides = []): void
    {
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $name = $reflection->getName();
            $parameters = $this->getWiredParameters($reflection, $overrides);

            $this->container->addEntry($name, new WiredEntry($name, $parameters));
        }
    }

    /**
     * Loads the parameter identifiers based on the constructor arguments and provided overrides.
     * @param \ReflectionClass $reflection The class reflection to inspect
     * @param string[] $overrides Override identifiers for constructor parameters
     * @return string[] Identifiers for constructor parameters
     */
    private function getWiredParameters(\ReflectionClass $reflection, array $overrides): array
    {
        $constructor = $reflection->getConstructor();

        if (!$constructor instanceof \ReflectionMethod) {
            return [];
        }

        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = '$' . $parameter->getName();

            if (isset($overrides[$name])) {
                $parameters[] = $overrides[$name];
                continue;
            }

            $type = $parameter->getType();

            if (!$type instanceof \ReflectionType || $type->isBuiltin()) {
                throw new \InvalidArgumentException(
                    sprintf("Missing autowired parameter '%s' for '%s'", $name, $reflection->getName())
                );
            }

            $parameters[] = $type->getName();
        }

        return $parameters;
    }
}
