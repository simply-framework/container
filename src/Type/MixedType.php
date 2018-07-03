<?php

namespace Simply\Container\Type;

use Simply\Container\DelegateContainer;

/**
 * MixedType.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class MixedType implements TypeInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function createFromCacheParameters(array $parameters): TypeInterface
    {
        /** @var MixedType $type */
        $type = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();

        [
            $type->value,
        ] = $parameters;

        return $type;
    }

    public function getCacheParameters(): array
    {
        return [
            $this->value
        ];
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function getValue(DelegateContainer $container)
    {
        if ($this->value instanceof \Closure) {
            return ($this->value)($container);
        }

        return $this->value;
    }
}
