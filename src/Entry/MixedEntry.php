<?php

namespace Simply\Container\Entry;

use Psr\Container\ContainerInterface;

/**
 * A standard container entry that may be any value or a closure used to determine that value.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MixedEntry implements EntryInterface
{
    /** @var mixed The value for the entry */
    private $value;

    /**
     * MixedType constructor.
     * @param mixed $value The value for the entry
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** {@inheritdoc} */
    public static function createFromCacheParameters(array $parameters): EntryInterface
    {
        return new self($parameters[0]);
    }

    /** {@inheritdoc} */
    public function getCacheParameters(): array
    {
        return [$this->value];
    }

    /** {@inheritdoc} */
    public function isFactory(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function getValue(ContainerInterface $container)
    {
        if ($this->value instanceof \Closure) {
            return ($this->value)($container);
        }

        return $this->value;
    }
}
