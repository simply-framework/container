<?php

namespace Simply\Container;

use Psr\Container\ContainerInterface;
use Simply\Container\Exception\ContainerException;
use Simply\Container\Exception\NotFoundException;
use Simply\Container\Type\MixedType;
use Simply\Container\Type\TypeInterface;

/**
 * Dependency Injection Container that supports different types of values.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2017-2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Container implements ContainerInterface, \ArrayAccess
{
    protected $types = [];

    protected $parameters = [];

    /** @var TypeInterface[] Cached container types */
    private $typeCache;

    private $valueCache;

    private $delegate;

    /**
     * Container constructor.
     * @param ContainerInterface|null $delegate Optional delegate container
     */
    public function __construct(ContainerInterface $delegate = null)
    {
        $this->typeCache = [];
        $this->valueCache = [];
        $this->delegate = new DelegateContainer($delegate ?: $this);
    }

    public static function loadCache(string $file, ContainerInterface $delegate = null): Container
    {
        return require $file;
    }

    public function getCacheFile(): string
    {
        $template = <<<'TEMPLATE'
<?php return new class ($delegate) extends \Simply\Container\Container {
    protected $types = ['TYPES'];
    protected $parameters = ['PARAMETERS'];
}
TEMPLATE;

        foreach ($this->types as $id => $class) {
            if (!isset($this->parameters[$id])) {
                $parameters = $this->typeCache[$id]->getCacheParameters();

                if (!$this->isConstantValue($parameters)) {
                    throw new ContainerException("Unable to cache entry '$value', the cache parameters are not static");
                }

                $this->parameters[$id] = $parameters;
            }
        }

        ksort($this->types);
        ksort($this->parameters);

        return strtr($template, [
            "['TYPES']" => var_export($this->types, true),
            "['PARAMETERS']" => var_export($this->parameters, true),
        ]);

    }

    private function isConstantValue($value): bool
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isConstantValue($item)) {
                    return false;
                }
            }
        }

        return $value === null || is_scalar($value);
    }

    public function addEntry(string $id, TypeInterface $type)
    {
        if (isset($this->types[$id])) {
            throw new \InvalidArgumentException("Entry for identifier '$id' already exists");
        }

        $this->types[$id] = \get_class($type);
        $this->typeCache[$id] = $type;
    }

    public function getOptionalPath(string $path, $default)
    {
        $resolver = new ContainerPathResolver($this);
        return $resolver->getOptional($path, $default);
    }

    public function getPath(string $path)
    {
        $resolver = new ContainerPathResolver($this);
        return $resolver->get($path);
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->valueCache)) {
            return $this->valueCache[$id];
        }

        $type = $this->getType($id);
        $value = $type->getValue($this->delegate);

        if ($type->isCacheable()) {
            $this->valueCache[$id] = $value;
        } else {
            $this->typeCache[$id] = $type;
        }

        return $value;
    }

    private function getType(string $id): TypeInterface
    {
        if (isset($this->typeCache[$id])) {
            return $this->typeCache[$id];
        }

        if (isset($this->types[$id])) {
            /** @var TypeInterface $typeClass */
            $typeClass = $this->types[$id];
            return $typeClass::createFromCacheParameters($this->parameters[$id]);

        }

        throw new NotFoundException("No entry was found for the identifier '$id'");
    }

    public function has($id): bool
    {
        return isset($this->types[$id]);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->addEntry($offset, new MixedType($value));
    }

    public function offsetUnset($offset): void
    {
        unset(
            $this->types[$offset],
            $this->parameters[$offset],
            $this->typeCache[$offset],
            $this->valueCache[$offset]
        );
    }
}
