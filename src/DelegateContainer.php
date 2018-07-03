<?php

namespace Simply\Container;

use Psr\Container\ContainerInterface;
use Simply\Container\Exception\NotFoundException;

/**
 * Container which is provided for dependency resolution to lookup dependencies via the delegated container.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class DelegateContainer implements ContainerInterface
{
    /** @var ContainerInterface The delegated container used for dependency resolution */
    private $container;

    /**
     * DelegateContainer constructor.
     * @param ContainerInterface $container The delegated container used for dependency resolution
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the delegated container.
     * @return ContainerInterface The delegated container
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Returns the entry based on the given dot path or the default value when the entry does not exist.
     * @param string $path The dot path to lookup
     * @param mixed $default The default value to return when the entry does not exist
     * @return mixed The value for the entry or the default value when the entry does not exist
     */
    public function getOptionalPath(string $path, $default)
    {
        $resolver = new ContainerPathResolver($this->container);
        return $resolver->getOptional($path, $default);
    }

    /**
     * Returns the entry based on the given dot path.
     * @param string $path The dot path to lookup
     * @return mixed The value for the entry
     * @throws NotFoundException If the entry cannot be found
     */
    public function getPath(string $path)
    {
        $resolver = new ContainerPathResolver($this->container);
        return $resolver->get($path);
    }

    /**
     * Returns the entry from the container with the given identifier.
     * @param string $id The entry identifier to look for
     * @return mixed The value for the entry
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Tells if the entry with the given identifier exists in the container.
     * @param string $id The entry identifier to look for
     * @return bool True if an entry with the given identifier exists, false otherwise
     */
    public function has($id): bool
    {
        return $this->container->has($id);
    }
}
