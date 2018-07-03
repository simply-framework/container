<?php

namespace Simply\Container\Type;

use Simply\Container\DelegateContainer;

/**
 * TypeInterface.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface TypeInterface
{
    public static function createFromCacheParameters(array $parameters): TypeInterface;
    public function getCacheParameters(): array;
    public function isCacheable(): bool;
    public function getValue(DelegateContainer $container);
}
