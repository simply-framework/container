<?php

namespace Simply\Container;

/**
 * Interface for classes that provide container entries.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
interface EntryProvider
{
    /**
     * Loads the entry provider when loaded by the container.
     * @return static The initialized entry provider
     */
    public static function initialize(): self;

    /**
     * Returns list of provided container entries and associated methods.
     * @return string[] List of provided container entries and associated methods
     */
    public function getMethods(): array;
}
