<?php

namespace Simply\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContainerTest extends TestCase
{
    public function testCallChaining()
    {
        $container = (new Container())
            ->set(['standard' => 'standard entry'])
            ->setValues(['value' => 'value entry', 'injection_argument' => ['injection' => 'injection value']])
            ->setBlueprints([Container::class => []])
            ->setFactories(['factory' => function () {
                return 'factory entry';
            }])
            ->setInjections([Container::class => ['set' => ['injection_argument']]]);

        $this->assertSame('standard entry', $container->get('standard'));
        $this->assertSame('value entry', $container->get('value'));
        $this->assertSame('factory entry', $container->get('factory'));

        $blueprint = $container->get(Container::class);
        $this->assertInstanceOf(Container::class, $blueprint);
        $this->assertSame('injection value', $blueprint->get('injection'));
    }

    public function testStandardTypeValue()
    {
        $container = new Container();
        $container->set(['foo' => 'bar']);

        $this->assertSame('bar', $container->get('foo'));
    }

    public function testStandardTypeValueWithInvokable()
    {
        $number = 1;

        $callable = function () use (& $number) {
            $number += 1;
            return $number;
        };

        $container = new Container();
        $container->set(['callable' => $callable]);

        $this->assertSame(2, $container->get('callable'));
        $this->assertSame(2, $container->get('callable'));
        $this->assertSame(2, $number);
    }

    public function testPlainTypeValue()
    {
        $callable = function () {
            return true;
        };

        $container = new Container();
        $container->setValues(['callable' => $callable]);

        $this->assertSame($callable, $container->get('callable'));
    }

    public function testFactoryTypeValue()
    {
        $number = 1;

        $callable = function () use (& $number) {
            $number += 1;
            return $number;
        };

        $container = new Container();
        $container->setFactories(['callable' => $callable]);

        $this->assertSame(2, $container->get('callable'));
        $this->assertSame(3, $container->get('callable'));
        $this->assertSame(3, $number);
    }

    public function testBlueprintTypeValue()
    {
        $container = new Container();
        $time = date('r');

        $container['current_time'] = $time;
        $container->setBlueprints(['foo' => [
            'class'       => \DateTime::class,
            '__construct' => ['current_time'],
        ]]);

        $instance = $container->get('foo');

        $this->assertInstanceOf(\DateTime::class, $instance);
        $this->assertSame($time, $instance->format('r'));
        $this->assertSame($instance, $container->get('foo'));
    }

    public function testInjections()
    {
        $container = new Container();
        $time = date('r');

        $container['current_time'] = $time;
        $container['modification'] = '+1 hour';
        $container->setBlueprints(['foo' => [
            'class'       => \DateTime::class,
            '__construct' => ['current_time'],
            ['modify', 'modification'],
        ]]);

        $container->setInjections([
            \DateTimeInterface::class => ['modify' => ['modification']],
        ]);

        $instance = $container->get('foo');
        $this->assertSame(date('r', strtotime('+2 hours', strtotime($time))), $instance->format('r'));
    }

    public function testIdentifierPath()
    {
        $class = new \stdClass();
        $class->normalValue = 'foo';
        $class->nullValue = null;

        $arrayObject = new \ArrayObject(['foo' => 'bar']);

        $subContainer = new Container();
        $subContainer->set(['id' => 'entry']);

        $magic = new class() {
            private $data = ['priv' => 'value'];

            public function __isset($name)
            {
                return isset($this->data[$name]);
            }

            public function __get($name)
            {
                return $this->data[$name];
            }
        };

        $container = new Container();
        $container->set([
            'array'       => ['key' => 'value'],
            'class'       => $class,
            'container'   => $subContainer,
            'arrayObject' => $arrayObject,
            'magic'       => $magic,
        ]);

        $this->assertSame('value', $container->getPath('array.key'));
        $this->assertSame('foo', $container->getPath('class.normalValue'));
        $this->assertNull($container->getPath('class.nullValue'));
        $this->assertSame('entry', $container->getPath('container.id'));
        $this->assertSame('bar', $container->getPath('arrayObject.foo'));
        $this->assertSame('value', $container->getPath('magic.priv'));
    }

    public function testIdentifierPathDefaultValue()
    {
        $container = new Container();
        $container->set(['foo' => []]);

        $this->assertSame('baz', $container->getPath('foo.bar', 'baz'));
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
        $container->set(['foo' => 'bar']);

        $this->expectException(ContainerExceptionInterface::class);
        $container->set(['foo' => 'bar']);
    }

    public function testDuplicateInjections()
    {
        $container = new Container();
        $container->setInjections([\DateTimeInterface::class => ['modify' => ['modification']]]);

        $this->expectException(ContainerExceptionInterface::class);
        $container->setInjections([\DateTimeInterface::class => ['modify' => ['modification']]]);
    }

    public function testInvalidKey()
    {
        $container = new Container();

        $this->expectException(ContainerExceptionInterface::class);
        $container->get('foo');
    }

    public function testPathNotFound()
    {
        $container = new Container();
        $container->set(['foo' => ['bar' => 'baz']]);

        $this->expectException(NotFoundExceptionInterface::class);
        $container->getPath('foo.baz');
    }

    public function testPathNotFoundInObject()
    {
        $object = new \stdClass();
        $object->bar = 'baz';

        $container = new Container();
        $container->set(['foo' => $object]);

        $this->expectException(NotFoundExceptionInterface::class);
        $container->getPath('foo.baz');
    }

    public function testInvalidIdentifierPath()
    {
        $container = new Container();
        $container->set(['foo' => 'bar']);

        $this->expectException(NotFoundExceptionInterface::class);
        $container->getPath('foo.bar');
    }

    public function testInvalidBlueprint()
    {
        $container = new Container();
        $container->setBlueprints(['stdClass' => ['foo' => 'bar']]);

        $this->expectException(ContainerExceptionInterface::class);
        $container->getPath('stdClass');
    }
}
