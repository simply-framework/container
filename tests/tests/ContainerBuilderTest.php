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
}
