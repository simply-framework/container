<?php

namespace Simply\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Simply\Container\Exception\ContainerException;
use Simply\Container\Exception\NotFoundException;

/**
 * Dependency Injection Container that supports different types of values.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Container implements ContainerInterface, \ArrayAccess
{
    /** Type for value entries */
    private const TYPE_VALUE = 1;

    /** Type for standard entries */
    private const TYPE_STANDARD = 2;

    /** Type for blueprint entries */
    private const TYPE_BLUEPRINT = 3;

    /** Type for factory entries */
    private const TYPE_FACTORY = 4;

    /** @var ContainerInterface The delegate container */
    private $delegate;

    /** @var int[] The types for different container entries */
    private $types;

    /** @var array The container entries */
    private $entries;

    /** @var array[] Additional injections for blueprint entries */
    private $injections;

    /** @var \Closure[] Callables used to resolve different entry types */
    private $resolvers;

    /**
     * Container constructor.
     * @param ContainerInterface|null $delegate Optional delegate container
     */
    public function __construct(ContainerInterface $delegate = null)
    {
        $this->delegate = $delegate;
        $this->types = [];
        $this->entries = [];
        $this->injections = [];

        $this->resolvers = [
            self::TYPE_VALUE => function ($id) {
                return $this->getPlainValue($id);
            },
            self::TYPE_STANDARD => function ($id) {
                return $this->getStandardValue($id);
            },
            self::TYPE_BLUEPRINT => function ($id) {
                return $this->getBlueprintValue($id);
            },
            self::TYPE_FACTORY => function ($id) {
                return $this->getFactoryValue($id);
            },
        ];
    }

    /**
     * Sets new standard entries.
     *
     * A standard entry may either be a plain PHP value or an invokable. If an
     * invokable is provided, that invokable is called with the container as a
     * parameter and the return value is used as the value for the entry. Note
     * that value returned by the invokable is cached and the same value is
     * returned for further requests for that entry.
     *
     * @param array $values Array of entry id-value pairs
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the keys already exist
     */
    public function set(array $values)
    {
        return $this->setEntries($values, self::TYPE_STANDARD);
    }

    /**
     * Sets new value entries.
     *
     * A value entry is any PHP value and is simply returned as is when requested,
     * even if the provided value is an invokable.
     *
     * @param array $values Array of entry id-value pairs
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the keys already exist
     */
    public function setValues(array $values)
    {
        return $this->setEntries($values, self::TYPE_VALUE);
    }

    /**
     * Sets new blueprint entries.
     *
     * A blueprint entry is an instruction on how to create a new instance as
     * the value for the entry. The blueprint entry must be an an array that
     * defines how to instantiate a class.
     *
     * The name of the class may be provided via a key labeled `class`. If no
     * such key exists, then the entry identifier itself is used as the name of
     * the class.
     *
     * The constructor arguments for the instance can be provided via a key
     * labeled `__construct`. However, if neither `class` nor `__construct` keys
     * exist in the array, The values of the array are used as the constructor
     * arguments.
     *
     * Additional methods for the instance may be called by defining additional
     * values to the array. If the key of the value is a string, then that
     * string is used as the name of the method to call and the value, which
     * must be an array, as the arguments for the method. However, for integer
     * keys, the value must be an array that defines the name of the method as
     * the first value and the arguments in the rest of the values.
     *
     * Please note that argument values MUST be identifiers for container
     * entries.
     *
     * @param array[] $blueprints Array of entry id-value pairs
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the keys already exist
     */
    public function setBlueprints(array $blueprints)
    {
        return $this->setEntries($blueprints, self::TYPE_BLUEPRINT);
    }

    /**
     * Sets new factory entries.
     *
     * A factory entry is simply a callable that is always called to determine
     * the value for the entry (in comparison to a standard entry, which is only
     * called once). Factories may be any kind of callables and the callable is
     * always called with the container as the parameter.
     *
     * @param callable[] $factories Array of entry id-value pairs
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the keys already exist
     */
    public function setFactories(array $factories)
    {
        return $this->setEntries($factories, self::TYPE_FACTORY);
    }

    /**
     * Sets new container entries with given type.
     * @param array $entries Array of entry id-value pairs
     * @param int $type Type of the new container entries
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the keys already exist
     */
    private function setEntries(array $entries, int $type)
    {
        $union = $this->entries + $entries;

        if (count($union) !== count($this->entries) + count($entries)) {
            throw new ContainerException('Cannot overwrite existing container entries');
        }

        $this->entries = $union;

        foreach ($entries as $key => $_) {
            $this->types[$key] = $type;
        }

        return $this;
    }

    /**
     * Adds additional injections for blueprints.
     *
     * Injections are additional method calls for blueprints. Each key in the
     * provided array defines either a class or an interface. If any instance
     * is created via a blueprint and it implements or extends any of these
     * keys, the additional method calls for the key are also called for the
     * instance.
     *
     * The injection method calls are defined the same way as additional method
     * calls in blueprints.
     *
     * @param array[] $injections Additional method calls for blueprints
     * @return $this Returns self for call chaining
     * @throws ContainerExceptionInterface If any of the injections are already defined
     */
    public function setInjections(array $injections)
    {
        if (array_intersect_key($injections, $this->injections)) {
            throw new ContainerException('Duplicate blueprint injections');
        }

        $this->injections += $injections;
        return $this;
    }

    /**
     * Returns a container entry based on a given dot path.
     *
     * The doth path is a period separated string, which indicates the entry
     * identifier and the keys of further traversed values. The traversed
     * values may be additional containers, arrays or objects with or without
     * array access. For example, assuming the entry is an array, the
     * following two would be equivalent:
     *
     *   - `$container->get('config')['session']['name']`
     *   - `$container->getPath('config.session.name')`
     *
     * Note that if a delegate container has been set, the initial lookup is
     * performed on the delegate container.
     *
     * This method also allows providing a default value that is returned when
     * the provided path cannot be found within the container. If no default
     * value has been provided, an exception will be thrown instead.
     *
     * @param string $path The identifier path to load
     * @param mixed $default Optional default value if not found
     * @return mixed The value for the path
     * @throws NotFoundExceptionInterface If no entry exist with the given path
     */
    public function getPath(string $path, $default = null)
    {
        try {
            $container = $this->delegate ?: $this;
            $resolver = new KeyPathResolver($container);
            return $resolver->get(explode('.', $path));
        } catch (NotFoundException $exception) {
            if (func_num_args() === 1) {
                throw new NotFoundException("No entry exists for the path '$path'", 0, $exception);
            }

            return $default;
        }
    }

    /**
     * Returns the entry with the given identifier.
     * @param string $id The identifier to find
     * @return mixed The value for the entry
     * @throws NotFoundExceptionInterface If no entry exists with the given identifier
     */
    public function get($id)
    {
        $id = (string) $id;

        if (!isset($this->types[$id])) {
            throw new NotFoundException("No entry was found for the identifier '$id'");
        }

        return $this->resolvers[$this->types[$id]]($id);
    }

    /**
     * Returns the value for the value entry.
     * @param string $id The identifier for the entry
     * @return mixed The value for the entry
     */
    private function getPlainValue(string $id)
    {
        return $this->entries[$id];
    }

    /**
     * Returns the value for standard entry.
     * @param string $id The identifier for the entry
     * @return mixed The value for the entry
     */
    private function getStandardValue(string $id)
    {
        if (is_object($this->entries[$id]) && method_exists($this->entries[$id], '__invoke')) {
            $this->entries[$id] = $this->entries[$id]($this->delegate ?: $this);
        }

        $this->types[$id] = self::TYPE_VALUE;
        return $this->entries[$id];
    }

    /**
     * Returns the value for blueprint entry.
     * @param string $id The identifier for the entry
     * @return object The value for the entry
     * @throws NotFoundExceptionInterface If the blueprint refers to undefined entries
     */
    private function getBlueprintValue(string $id)
    {
        $blueprint = $this->entries[$id];

        $class = $blueprint['class'] ?? $id;
        $arguments = $blueprint['__construct'] ?? (isset($blueprint['class']) ? [] : $blueprint);

        $instance = new $class(... $this->loadArguments($arguments));

        unset($blueprint['class'], $blueprint['__construct']);
        $this->applyBlueprint($instance, $blueprint);

        foreach ($this->injections as $type => $blueprint) {
            if ($instance instanceof $type) {
                $this->applyBlueprint($instance, $blueprint);
            }
        }

        $this->types[$id] = self::TYPE_VALUE;
        return $this->entries[$id] = $instance;
    }

    /**
     * Applies a blueprint to a specific instance.
     * @param object $instance The instance to use
     * @param array[] $blueprint The blueprint to apply
     * @throws NotFoundExceptionInterface If the blueprint refers to undefined entries
     */
    private function applyBlueprint($instance, array $blueprint): void
    {
        foreach ($blueprint as $method => $arguments) {
            if (is_int($method)) {
                $method = array_shift($arguments);
            }

            call_user_func_array([$instance, $method], $this->loadArguments($arguments));
        }
    }

    /**
     * Loads the list of arguments using a dot path.
     * @param string[] $arguments List of argument paths to load
     * @return array The loaded arguments
     * @throws NotFoundExceptionInterface If any of the argument paths refer to undefined entries
     */
    private function loadArguments(array $arguments): array
    {
        return array_map(function (string $value) {
            return $this->getPath($value);
        }, $arguments);
    }

    /**
     * Returns the value for factory type entry.
     * @param string $id The identifier for the entry
     * @return mixed The value for the entry
     */
    private function getFactoryValue(string $id)
    {
        return $this->entries[$id]($this->delegate ?: $this);
    }

    /**
     * Tells if the container has an entry with the given identifier.
     * @param string $id The identifier to find
     * @return bool True if the entry exists, false if not
     */
    public function has($id): bool
    {
        return isset($this->types[(string) $id]);
    }

    /**
     * Tells if the container has an entry with the given identifier.
     * @param string $offset The entry identifier
     * @return bool True if the entry exists, false if not
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Returns the value for entry with the given identifier.
     * @param string $offset The entry identifier
     * @return mixed The value for the entry
     * @throws NotFoundExceptionInterface If no entry exists with the given identifier
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets the value of the standard entry with the given identifier.
     * @param string $offset The entry identifier
     * @param mixed $value The value for the standard entry
     */
    public function offsetSet($offset, $value): void
    {
        $this->types[(string) $offset] = self::TYPE_STANDARD;
        $this->entries[(string) $offset] = $value;
    }

    /**
     * Removes the entry from the container with the given identifier.
     * @param string $offset The entry identifier
     */
    public function offsetUnset($offset): void
    {
        unset($this->entries[(string) $offset], $this->types[(string) $offset]);
    }
}
