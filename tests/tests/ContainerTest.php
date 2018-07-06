<?php

namespace Simply\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Simply\Container\Entry\CallableEntry;
use Simply\Container\Entry\EntryInterface;
use Simply\Container\Entry\FactoryEntry;
use Simply\Container\Entry\MixedEntry;
use Simply\Container\Entry\ProviderEntry;
use Simply\Container\Exception\ContainerException;
use Simply\Container\Exception\NotFoundException;

/**
 * ContainerTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ContainerTest extends TestCase
{
    public function testStandardTypeValue()
    {
        $this->withContainer([
            'foo' => 'bar',
        ], function (Container $container) {
            $this->assertSame('bar', $container->get('foo'));
        });
    }

    public function testStandardTypeValueWithClosure()
    {
        $number = 1;

        $callable = function () use (& $number) {
            return $number++;
        };

        $container = $this->getContainer([
            'callable' => $callable,
        ]);

        $this->assertSame(1, $container->get('callable'));
        $this->assertSame(1, $container->get('callable'));
        $this->assertSame(2, $number);
    }

    public function testUncacheableEntry()
    {
        $container = $this->getContainer([
            'callable' => function () {
                return 'foobar';
            },
        ]);

        $this->expectException(ContainerException::class);
        $container->getCacheFile();
    }

    public function testFactoryTypeValue()
    {
        $testClass = new class() {
            public static function getDate(): \DateTime
            {
                return new \DateTime();
            }
        };

        $callable = [\get_class($testClass), 'getDate'];

        $this->withContainer([
            'cached' => new CallableEntry($callable),
            'uncached' => new FactoryEntry(new CallableEntry($callable)),
        ], function (Container $container) {
            $cached = $container->get('cached');
            $uncached = $container->get('uncached');

            $this->assertSame($cached, $container->get('cached'));
            $this->assertNotSame($uncached, $container->get('uncached'));
        });
    }

    public function testProviderTypeValue()
    {
        $testClass = new class() extends AbstractEntryProvider {
            public function getFoo()
            {
                return 'foo';
            }
        };

        $this->withContainer([
            \get_class($testClass) => new CallableEntry([\get_class($testClass), 'initialize']),
            'foo_value' => new ProviderEntry([$testClass, 'getFoo']),
        ], function (Container $container) {
            $this->assertSame('foo', $container->get('foo_value'));
        });
    }

    public function testArrayAccess()
    {
        $container = new Container();

        $container['foo'] = 'bar';

        $this->assertTrue(isset($container['foo']));
        $this->assertSame('bar', $container['foo']);

        unset($container['foo']);

        $this->assertFalse(isset($container['foo']));
    }

    public function testDuplicateKeys()
    {
        $container = new Container();
        $container->addEntry('foo', new MixedEntry('bar'));

        $this->expectException(ContainerException::class);
        $container->addEntry('foo', new MixedEntry('bar'));
    }

    public function testInvalidKey()
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $container->get('foo');
    }

    public function testContainerDelegation()
    {
        $container = $this->getContainer(['foo' => 'bar']);
        $delegate = $this->getContainer(['other' => 'value']);

        $container->setDelegate($delegate);
        $container['callable'] = function (ContainerInterface $container) use ($delegate) {
            $this->assertSame($delegate, $container);
            $this->assertTrue($container->has('other'));
            $this->assertSame('value', $container->get('other'));

            return 'called';
        };

        $this->assertSame('called', $container->get('callable'));
    }

    public function testDoubleCaching()
    {
        $this->withContainer([
            'foo' => 'bar',
        ], function (Container $container) {
            $container->addEntry('bar', new MixedEntry('baz'));
            $cache = $container->getCacheFile();

            /** @var Container $cached */
            $cached = eval(substr($cache, 5));

            $this->assertSame('bar', $cached->get('foo'));
            $this->assertSame('baz', $cached->get('bar'));
        });
    }

    private function withContainer(array $values, \Closure $suite)
    {
        $suite($this->getContainer($values));
        $suite($this->getCachedContainer($values));
    }

    private function getCachedContainer(array $values)
    {
        $container = $this->getContainer($values);
        $code = $container->getCacheFile();

        return eval(substr($code, \strlen('<?php ')));
    }

    private function getContainer(array $values)
    {
        $container = new Container();

        foreach ($values as $key => $value) {
            if (!$value instanceof EntryInterface) {
                $value = new MixedEntry($value);
            }

            $container->addEntry($key, $value);
        }

        return $container;
    }
}
