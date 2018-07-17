<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerInterface;

/**
 * Represents an entry that is wired based on constructor arguments.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class WiredEntry implements EntryInterface
{
    /** @var string The name of the class to instantiate */
    private $class;

    /** @var string[] The identifiers of the container entries to pass to the constructor */
    private $arguments;

    /**
     * WiredEntry constructor.
     * @param string $class The name of the class to instantiate
     * @param string[] $arguments The identifiers of the container entries to pass to the constructor
     */
    public function __construct(string $class, array $arguments)
    {
        $this->class = $class;
        $this->setArguments(... $arguments);
    }

    /**
     * Sets the identifiers for constructor arguments for the wired entry.
     * @param string ...$arguments The identifiers for constructor arguments
     */
    private function setArguments(string ... $arguments): void
    {
        $this->arguments = $arguments;
    }

    /** {@inheritdoc} */
    public static function createFromCacheParameters(array $parameters): EntryInterface
    {
        [
            $class,
            $arguments,
        ] = $parameters;

        return new static($class, $arguments);
    }

    /** {@inheritdoc} */
    public function getCacheParameters(): array
    {
        return [
            $this->class,
            $this->arguments,
        ];
    }

    /** {@inheritdoc} */
    public function isFactory(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getValue(ContainerInterface $container)
    {
        $values = array_map(function (string $name) use ($container) {
            return $container->get($name);
        }, $this->arguments);

        return new $this->class(... $values);
    }
}
