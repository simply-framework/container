<?php

namespace Simply\Container;

use Psr\Container\ContainerInterface;
use Simply\Container\Exception\NotFoundException;

/**
 * Provides methods for traversing variables according to a key path.
 */
class KeyPathResolver
{
    /** @var mixed The initial value used for traversing */
    private $value;

    /** @var \Closure[] Callables used to traverse the path values */
    private $traversalMethods;

    /**
     * KeyPathResolver constructor.
     * @param mixed $value The initial value used to traversing
     */
    public function __construct($value)
    {
        $this->value = $value;
        $this->initializeTraversalMethods();
    }

    /**
     * Initializes the traversal methods used to traverse values.
     */
    private function initializeTraversalMethods(): void
    {
        $this->traversalMethods = [
            function (& $value, $key) {
                return $this->traverseArray($value, $key);
            },
            function (& $value, $key) {
                return $this->traverseContainer($value, $key);
            },
            function (& $value, $key) {
                return $this->traverseArrayAccess($value, $key);
            },
            function (& $value, $key) {
                return $this->traverseMagicMethods($value, $key);
            },
            function (& $value, $key) {
                return $this->traverseObjectVariables($value, $key);
            },
        ];
    }

    /**
     * Returns a value from the given key path.
     * @param array $path A list of keys used to traverse the value
     * @return mixed The value for the key path
     * @throws NotFoundException If a key in the path does not exist
     */
    public function get(array $path)
    {
        $value = $this->value;

        foreach ($path as $key) {
            foreach ($this->traversalMethods as $method) {
                if ($method($value, $key)) {
                    continue 2;
                }
            }

            throw new NotFoundException("Undefined key '$key' in the traversed path");
        }

        return $value;
    }

    /**
     * Traverses a key in an array.
     * @param mixed $value The variable to traverse
     * @param mixed $key The key used for traversing
     * @return bool True if the value was traversed, false if not
     */
    private function traverseArray(& $value, $key): bool
    {
        if (is_array($value) && array_key_exists($key, $value)) {
            $value = $value[$key];
            return true;
        }

        return false;
    }

    /**
     * Traverses a PSR-11 compatible container.
     * @param mixed $value The variable to traverse
     * @param mixed $key The key used for traversing
     * @return bool True if the value was traversed, false if not
     */
    private function traverseContainer(& $value, $key): bool
    {
        if ($value instanceof ContainerInterface && $value->has($key)) {
            $value = $value->get($key);
            return true;
        }

        return false;
    }

    /**
     * Traverses an object that implements ArrayAccess.
     * @param mixed $value The variable to traverse
     * @param mixed $key The key used for traversing
     * @return bool True if the value was traversed, false if not
     */
    private function traverseArrayAccess(& $value, $key): bool
    {
        if ($value instanceof \ArrayAccess && $value->offsetExists($key)) {
            $value = $value->offsetGet($key);
            return true;
        }

        return false;
    }

    /**
     * Traverses an object that has magic methods to access properties.
     * @param mixed $value The variable to traverse
     * @param mixed $key The key used for traversing
     * @return bool True if the value was traversed, false if not
     */
    private function traverseMagicMethods(& $value, $key): bool
    {
        if (is_object($value) && method_exists($value, '__isset') && method_exists($value, '__get')) {
            if ($value->__isset($key)) {
                $value = $value->__get($key);
                return true;
            }
        }

        return false;
    }

    /**
     * Traverses publicly available properties in an object.
     * @param mixed $value The variable to traverse
     * @param mixed $key The key used for traversing
     * @return bool True if the value was traversed, false if not
     */
    private function traverseObjectVariables(& $value, $key): bool
    {
        if (is_object($value)) {
            $variables = get_object_vars($value);

            if (array_key_exists($key, $variables)) {
                $value = $variables[$key];
                return true;
            }
        }

        return false;
    }
}
