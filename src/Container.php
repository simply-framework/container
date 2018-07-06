<?php

namespace Simply\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Simply\Container\Entry\EntryInterface;
use Simply\Container\Entry\MixedEntry;
use Simply\Container\Exception\ContainerException;
use Simply\Container\Exception\NotFoundException;

/**
 * Dependency Injection Container that supports different types of entries.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017-2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Container implements ContainerInterface, \ArrayAccess
{
    /** @var string[] Lists the names of classes used by different container entries */
    protected $types = [];

    /** @var array[] Cached container entry parameters for initializing the entries */
    protected $parameters = [];

    /** @var EntryInterface[] Cached entries used to resolve values */
    private $entryCache;

    /** @var array Cached values for container entries */
    private $valueCache;

    /** @var ContainerInterface Delegate container provided for dependency resolution */
    private $delegate;

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this->entryCache = [];
        $this->valueCache = [];
        $this->delegate = $this;
    }

    /**
     * Sets the delegate container that is provided for dependency resolution.
     * @param ContainerInterface $delegate Delegate container to use for dependency resolution
     */
    public function setDelegate(ContainerInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Returns PHP code for a cached container that can be loaded quickly on runtime.
     * @return string The PHP code for the cached container
     * @throws ContainerException If the container contains entries that cannot be cached
     */
    public function getCacheFile(): string
    {
        $this->loadCacheParameters();

        ksort($this->types);
        ksort($this->parameters);

        $template = <<<'TEMPLATE'
<?php return new class extends \Simply\Container\Container {
    protected $types = ['TYPES'];
    protected $parameters = ['PARAMETERS'];
};
TEMPLATE;

        return strtr($template, [
            "['TYPES']" => var_export($this->types, true),
            "['PARAMETERS']" => var_export($this->parameters, true),
        ]);
    }

    /**
     * Loads the cache parameters for all entries.
     * @throws ContainerException If the container contains entries that cannot be cached
     */
    private function loadCacheParameters()
    {
        foreach ($this->types as $id => $class) {
            if (!isset($this->entryCache[$id])) {
                continue;
            }

            $parameters = $this->entryCache[$id]->getCacheParameters();

            if (!$this->isConstantValue($parameters)) {
                throw new ContainerException("Unable to cache entry '$id', the cache parameters are not static");
            }

            $this->parameters[$id] = $parameters;
        }
    }

    /**
     * Tells if the given value is a static PHP value.
     * @param mixed $value The value to test
     * @return bool True if the value is a static PHP value, false if not
     */
    private function isConstantValue($value): bool
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isConstantValue($item)) {
                    return false;
                }
            }

            return true;
        }

        return $value === null || is_scalar($value);
    }

    /**
     * Adds an entry to the container.
     * @param string $id The identifier for the container entry
     * @param EntryInterface $type The container entry to add
     * @throws ContainerException If trying to add an entry to an identifier that already exists
     */
    public function addEntry(string $id, EntryInterface $type)
    {
        if (isset($this->types[$id])) {
            throw new ContainerException("Entry for identifier '$id' already exists");
        }

        $this->types[$id] = \get_class($type);
        $this->entryCache[$id] = $type;
    }

    /**
     * Returns value for the container entry with the given identifier.
     * @param string $id The entry identifier to look for
     * @return mixed The value for the entry
     * @throws NotFoundExceptionInterface If the entry cannot be found
     * @throws ContainerExceptionInterface If there are errors trying to load dependencies
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->valueCache)) {
            return $this->valueCache[$id];
        }

        $entry = $this->getEntry($id);
        $value = $entry->getValue($this->delegate);

        if ($entry->isFactory()) {
            $this->entryCache[$id] = $entry;
        } else {
            $this->valueCache[$id] = $value;
        }

        return $value;
    }

    /**
     * Returns container entry for the given identifier.
     * @param string $id The entry identifier to look for
     * @return EntryInterface The container entry for the given identifier
     * @throws NotFoundExceptionInterface If the entry cannot be found
     */
    private function getEntry(string $id): EntryInterface
    {
        if (isset($this->entryCache[$id])) {
            return $this->entryCache[$id];
        }

        if (isset($this->types[$id])) {
            /** @var EntryInterface $entryClass */
            $entryClass = $this->types[$id];
            return $entryClass::createFromCacheParameters($this->parameters[$id]);
        }

        throw new NotFoundException("No entry was found for the identifier '$id'");
    }

    /**
     * Tells if the entry with the given identifier exists in the container.
     * @param string $id The entry identifier to look for
     * @return bool True if an entry with the given identifier exists, false otherwise
     */
    public function has($id): bool
    {
        return isset($this->types[$id]);
    }

    /**
     * Tells if the entry with the given identifier exists in the container.
     * @param string $offset The entry identifier to look for
     * @return bool True if an entry with the given identifier exists, false otherwise
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Returns value for the container entry with the given identifier.
     * @param string $offset The entry identifier to look for
     * @return mixed The value for the entry
     * @throws NotFoundExceptionInterface If the entry cannot be found
     * @throws ContainerExceptionInterface If there are errors trying to load dependencies
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Adds a container entry with a mixed type.
     * @param string $offset The identifier for the container entry
     * @param mixed $value The value for the container entry
     * @throws ContainerException If trying to add an entry to an identifier that already exists
     */
    public function offsetSet($offset, $value): void
    {
        $this->addEntry($offset, new MixedEntry($value));
    }

    /**
     * Removes an entry from the container.
     * @param string $offset The identifier of the container entry to remove
     */
    public function offsetUnset($offset): void
    {
        unset(
            $this->types[$offset],
            $this->parameters[$offset],
            $this->entryCache[$offset],
            $this->valueCache[$offset]
        );
    }
}
