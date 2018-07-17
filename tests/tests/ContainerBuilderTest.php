<?php

namespace Simply\Container;

use PHPUnit\Framework\TestCase;

/**
 * ContainerBuilderTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ContainerBuilderTest extends TestCase
{
    public function testConfiguration()
    {
        $builder = new ContainerBuilder();
        $builder->registerConfiguration([
            'foo' => 'foo_value',
            'bar' => 'bar_value',
        ]);

        $container = $builder->getContainer();

        $this->assertSame('foo_value', $container->get('foo'));
        $this->assertSame('bar_value', $container->get('bar'));
    }

    public function testProviderRegistration()
    {
        $provider = new class() extends AbstractEntryProvider {
            public function getDate(): \DateTime
            {
                return new \DateTime();
            }

            public function getNoType()
            {
                return new \DirectoryIterator(__DIR__);
            }

            public static function getStatic(): \DirectoryIterator
            {
                return new \DirectoryIterator(__DIR__);
            }

            protected function getProtected(): \DirectoryIterator
            {
                return new \DirectoryIterator(__DIR__);
            }

            public function __invoke(): \DirectoryIterator
            {
                return new \DirectoryIterator(__DIR__);
            }
        };

        $builder = new ContainerBuilder();
        $builder->registerProvider($provider);

        $container = $builder->getContainer();

        $this->assertInstanceOf(\DateTime::class, $container->get(\DateTime::class));
        $this->assertFalse($container->has(\DirectoryIterator::class));
    }

    public function testAutowiring()
    {
        $simple = new class() {
        };

        $complex = new class(new \DateTime(), '', new \DateTime()) {
            public $historyDate;
            public $addTime;
            public $nowDate;

            public function __construct(\DateTime $history, string $add, \DateTime $now)
            {
                $this->historyDate = $history;
                $this->addTime = $add;
                $this->nowDate = $now;
            }
        };

        $simpleClass = \get_class($simple);
        $complexClass = \get_class($complex);

        $builder = new ContainerBuilder();
        $builder->registerAutowiredClasses([$simpleClass, $complexClass], [
            '$add' => 'parameter.add',
            '$now' => 'parameter.now',
        ]);

        $dateHistory = new \DateTime('2010-10-10 10:00:00+0000');
        $dateNow = new \DateTime();

        $builder->registerConfiguration([
            \DateTime::class => $dateHistory,
            'parameter.add' => '+2 days',
            'parameter.now' => $dateNow,
        ]);

        $container = $builder->getContainer();

        $complexEntry = $container->get($complexClass);

        $this->assertInstanceOf($complexClass, $complexEntry);
        $this->assertSame($dateHistory, $complexEntry->historyDate);
        $this->assertSame('+2 days', $complexEntry->addTime);
        $this->assertSame($dateNow, $complexEntry->nowDate);

        $simpleEntry = $container->get($simpleClass);
        $this->assertInstanceOf($simpleClass, $simpleEntry);
    }

    public function testMissingWiredParameter()
    {
        $class = new class('') {
            public function __construct(string $foo)
            {
            }
        };

        $builder = new ContainerBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $builder->registerAutowiredClasses([\get_class($class)]);
    }
}
