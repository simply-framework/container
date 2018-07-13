<?php

namespace Simply\Container;

use Simply\Container\Entry\CallableEntry;
use Simply\Container\Entry\MixedEntry;
use Simply\Container\Entry\ProviderEntry;
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
}
