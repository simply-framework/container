<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Simply\Container\Exception\ContainerException;

/**
 * Interface to facilitate different kinds of container entries.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface EntryInterface
{
    /**
     * Initializes the entry from the given container cache parameters.
     * @param array $parameters The container cache parameters for the entry
     * @return static The initialized container entry
     */
    public static function createFromCacheParameters(array $parameters): self;

    /**
     * Returns the container cache parameters for the entry.
     * @return array The container cache parameters for the entry
     * @throws ContainerException If the entry cannot be cached
     */
    public function getCacheParameters(): array;

    /**
     * Tells if we should not cache the value because the entry is a factory.
     * @return bool True if the entry is factory and the value should not be cached, false otherwise
     */
    public function isFactory(): bool;

    /**
     * Returns the value for the container entry.
     * @param ContainerInterface $container Container used to resolve dependencies
     * @return mixed The value for the container entry
     * @throws NotFoundExceptionInterface If trying to use dependencies that do not exist
     * @throws ContainerExceptionInterface If there are errors trying to load dependencies
     */
    public function getValue(ContainerInterface $container);
}
