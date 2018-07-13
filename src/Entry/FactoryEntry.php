<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerInterface;

/**
 * Provides a wrapper for other entries to disallow caching of returned values.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FactoryEntry implements EntryInterface
{
    /** @var EntryInterface The actual container entry used to determine the value */
    private $entry;

    /**
     * FactoryType constructor.
     * @param EntryInterface $type The actual container entry used to determine the value
     */
    public function __construct(EntryInterface $type)
    {
        $this->entry = $type;
    }

    /** {@inheritdoc} */
    public static function createFromCacheParameters(array $parameters): EntryInterface
    {
        /** @var EntryInterface $class */
        [$class, $cacheParameters] = $parameters;

        return new static($class::createFromCacheParameters($cacheParameters));
    }

    /** {@inheritdoc} */
    public function getCacheParameters(): array
    {
        return [
            \get_class($this->entry),
            $this->entry->getCacheParameters(),
        ];
    }

    /** {@inheritdoc} */
    public function isFactory(): bool
    {
        return true;
    }

    /** {@inheritdoc} */
    public function getValue(ContainerInterface $container)
    {
        return $this->entry->getValue($container);
    }
}
