<?php

namespace Simply\Container\Type;

use Simply\Container\DelegateContainer;

/**
 * FactoryType.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FactoryType implements TypeInterface
{
    private $type;

    public function __construct(TypeInterface $type)
    {
        $this->type = $type;
    }

    public static function createFromCacheParameters(array $parameters): TypeInterface
    {
        /** @var TypeInterface $class */
        [$class, $cacheParameters] = $parameters;

        return new FactoryType($class::createFromCacheParameters($cacheParameters));
    }

    public function getCacheParameters(): array
    {
        return [
            \get_class($this->type),
            $this->type->getCacheParameters(),
        ];
    }

    public function isCacheable(): bool
    {
        return false;
    }

    public function getValue(DelegateContainer $container)
    {
        return $this->type->getValue($container);
    }
}
