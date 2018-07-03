<?php

namespace Simply\Container;

use Psr\Container\ContainerInterface;
use Simply\Container\Exception\NotFoundException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides functionality for resolving values for container entries based on dot separated paths.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017-2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ContainerPathResolver implements ContainerInterface
{
    /** @var ContainerInterface The container to use for resolving values */
    private $container;

    /** @var mixed The value that is currently being resolved */
    private $value;

    /**
     * ContainerPathResolver constructor.
     * @param ContainerInterface $container The container to use for resolving values
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the entry based on the given dot path or the default value when the entry does not exist.
     * @param string $path The dot path to lookup
     * @param mixed $default The default value to return when the entry does not exist
     * @return mixed The value for the entry or the default value when the entry does not exist
     */
    public function getOptional(string $path, $default)
    {
        try {
            return $this->get($path);
        } catch (NotFoundExceptionInterface $exception) {
            return $default;
        }
    }

    /**
     * Tells if the entry with the given dot path exists in the container.
     * @param string $path The entry dot path to look for
     * @return bool True if an entry with the given dot path exists, false otherwise
     */
    public function has($path): bool
    {
        try {
            $this->get($path);
            return true;
        } catch (NotFoundExceptionInterface $exception) {
            return false;
        }
    }

    /**
     * Returns the entry based on the given dot path.
     * @param string $path The dot path to lookup
     * @return mixed The value for the entry
     * @throws NotFoundException If the entry cannot be found
     */
    public function get($path)
    {
        if ($this->container->has($path)) {
            return $this->container->get($path);
        }

        $keys = explode('.', $path);

        $this->value = $this->container->get(array_shift($keys));

        while ($keys) {
            try {
                $this->traverseKey(implode('.', $keys));
                break;
            } catch (NotFoundExceptionInterface $exception) {
                $this->traverseKey(array_shift($keys));
            }
        }

        return $this->value;
    }

    /**
     * Traverses the given key in the currently traversed value.
     * @param string $key The key to traverse
     * @throws NotFoundException If the key cannot be traversed
     */
    private function traverseKey(string $key): void
    {
        switch (true) {
            case $this->traverseArray($key):
            case $this->traverseContainer($key):
            case $this->traverseArrayAccess($key):
            case $this->traverseObject($key):
                return;
            default:
                throw new NotFoundException("Unable to traverse key '$key' in the traversed path");
        }
    }

    /**
     * Traverses a key in an array value.
     * @param string $key The key to traverse
     * @return bool True if the value was traversed successfully, false if the value is not applicable
     * @throws NotFoundException If the value is applicable, but the key is not found
     */
    private function traverseArray(string $key): bool
    {
        if (!\is_array($this->value)) {
            return false;
        }

        if (!array_key_exists($key, $this->value)) {
            throw new NotFoundException("Undefined key '$key' in traversed array");
        }

        $this->value = $this->value[$key];
        return true;
    }

    /**
     * Traverses a key in a container value.
     * @param string $key The key to traverse
     * @return bool True if the value was traversed successfully, false if the value is not applicable
     * @throws NotFoundException If the value is applicable, but the key is not found
     */
    private function traverseContainer(string $key): bool
    {
        if (!$this->value instanceof ContainerInterface) {
            return false;
        }

        if (!$this->value->has($key)) {
            throw new NotFoundException("Undefined identifier '$key' in traversed container");
        }

        $this->value = $this->value->get($key);
        return true;
    }

    /**
     * Traverses a key in an ArrayAccess value.
     * @param string $key The key to traverse
     * @return bool True if the value was traversed successfully, false if the value is not applicable
     * @throws NotFoundException If the value is applicable, but the key is not found
     */
    private function traverseArrayAccess(string $key): bool
    {
        if (!$this->value instanceof \ArrayAccess) {
            return false;
        }

        if (!$this->value->offsetExists($key)) {
            throw new NotFoundException("Undefined key '$key' in traversed object");
        }

        $this->value = $this->value->offsetGet($key);
        return true;
    }

    /**
     * Traverses a key in an object value.
     * @param string $key The key to traverse
     * @return bool True if the value was traversed successfully, false if the value is not applicable
     * @throws NotFoundException If the value is applicable, but the key is not found
     */
    private function traverseObject(string $key): bool
    {
        if (!\is_object($this->value)) {
            return false;
        }

        $properties = get_object_vars($this->value);

        if (array_key_exists($key, $properties)) {
            $this->value = $properties[$key];
            return true;
        }

        if (!isset($this->value->$key)) {
            throw new NotFoundException("Undefined property '$key' in traversed object");
        }

        $this->value = $this->value->$key;
        return true;
    }
}
