<?php

namespace Simply\Container;

/**
 * Abstract entry provider that provides the most basic provider initialization functionality.
 *
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class AbstractEntryProvider implements EntryProvider
{
    /**
     * Returns a new instance of the entry provider.
     * @return EntryProvider New entry provider instance initialized with default constructor
     */
    public static function initialize(): EntryProvider
    {
        return new static();
    }
}
