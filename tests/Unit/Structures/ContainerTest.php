<?php

namespace Rudnev\Settings\Tests\Unit\Structures;

use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Structures\Container;

class ContainerTest extends TestCase
{
    public function testDefaultValuesCanBeSetAndRetrieved()
    {
        $container = new Container();
        $this->assertNull($container->getDefault('foo'));
        $this->assertEquals([], $container->getDefault());
        $container->setDefault('foo', 'bar');
        $container->setDefault(['bar' => 'baz', 'baz' => 'qux']);
        $this->assertEquals('bar', $container->getDefault('foo'));
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'qux'], $container->getDefault());
        $this->assertEquals('bar', $container['foo']);
        $this->assertNull($container['qux']);
        $container->forgetDefault('foo');
        $this->assertNull($container->getDefault('foo'));
        $container->forgetDefault(['bar', 'baz']);
        $this->assertEquals([], $container->getDefault());
        $container->setDefault('qux', 'pax');
        $container->forgetDefault();
        $this->assertEquals([], $container->getDefault());
    }
}