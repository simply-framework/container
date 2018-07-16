<?php

namespace Simply\Container\Entry;

use PHPUnit\Framework\TestCase;
use Simply\Container\Container;
use Simply\Container\Exception\ContainerException;

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

    public function testUnexpectedProviderValue()
    {
        $container = new Container();
        $date = new \DateTime();

        $container->addEntry('timestamp', new ProviderEntry([$date, 'getTimestamp']));
        $container->addEntry(\DateTime::class, new MixedEntry('Not A Object'));

        $this->expectException(ContainerException::class);
        $container->get('timestamp');
    }
}
