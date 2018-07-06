<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerInterface;

/**
 * Container entry that uses a callable to determine the value for the entry.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CallableEntry implements EntryInterface
{
    /** @var callable The callable used to determine the value for the entry */
    private $callable;

    /**
     * CallableEntry constructor.
     * @param mixed $callable The callable used to determine the value for the entry
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /** {@inheritdoc} */
    public static function createFromCacheParameters(array $parameters): EntryInterface
    {
        /** @var CallableEntry $entry */
        $entry = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
        $entry->callable = $parameters[0];

        return $entry;
    }

    /** {@inheritdoc} */
    public function getCacheParameters(): array
    {
        return [$this->callable];
    }

    /** {@inheritdoc} */
    public function isFactory(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getValue(ContainerInterface $container)
    {
        return ($this->callable)($container);
    }
}
