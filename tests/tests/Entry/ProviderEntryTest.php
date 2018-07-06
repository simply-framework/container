<?php

namespace Simply\Container\Entry;

use PHPUnit\Framework\TestCase;

/**
 * ProviderEntryTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ProviderEntryTest extends TestCase
{
    public function testInvalidCallableFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProviderEntry(function () {
            return 'foobar';
        });
    }
}
