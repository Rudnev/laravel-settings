<?php

namespace Rudnev\Settings\Tests\Unit\Stores;

use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Stores\ArrayStore;

class ArrayStoreTest extends TestCase
{
    public function testNameCanBeSetAndRetrieved()
    {
        $store = new ArrayStore;
        $store->setName('foo-bar');
        $this->assertEquals('foo-bar', $store->getName());
    }

    public function testScopeCanBeSetAndRetrieved()
    {
        $store = new ArrayStore;
        $scope = new Scope('foo');
        $store->setScope($scope);
        $this->assertEquals(spl_object_id($scope), spl_object_id($store->getScope()));
    }

    public function testHasMethod()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $this->assertTrue($store->has('foo'));
        $this->assertFalse($store->has('bar'));
    }

    public function testItemsCanBeSetAndRetrieved()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $this->assertEquals('bar', $store->get('foo'));
    }

    public function testMultipleItemsCanBeSetAndRetrieved()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $store->setMultiple([
            'fizz' => 'buz',
            'quz' => 'baz',
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $store->getMultiple(['foo', 'fizz', 'quz', 'norf']));

        // Dot syntax:

        $store = new ArrayStore;
        $store->set('products.desk.price', 200);
        $this->assertEquals(200, $store->get('products.desk.price'));
        $this->assertEquals(['products' => ['desk' => ['price' => 200]]], $store->all());
        $store->set('products.desk.height', 120);
        $this->assertEquals(['price' => 200, 'height' => 120], $store->get('products.desk'));
        $store->set('products.desk', ['price' => 300]);
        $this->assertEquals(['products' => ['desk' => ['price' => 300]]], $store->all());

        $store = new ArrayStore;
        $store->setMultiple([
            'foo' => 'bar',
            'qwe.asd' => 'zxc',
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'qwe' => ['asd' => 'zxc'],
            'norf' => null,
        ], $store->getMultiple(['foo', 'qwe', 'norf']));
    }

    public function testAllItemsCanBeRetrieved()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $store->set('bar', 'foo');
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $store->all());
    }

    public function testItemsCanBeRemoved()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $store->forget('foo');
        $this->assertNull($store->get('foo'));
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $store = new ArrayStore;
        $store->setMultiple(['foo' => 1, 'bar' => 2, 'qux' => 3]);
        $store->forgetMultiple(['foo', 'qux']);
        $this->assertNull($store->get('foo'));
        $this->assertNull($store->get('qux'));
    }

    public function testItemsCanBeFlushed()
    {
        $store = new ArrayStore;
        $store->set('foo', 'bar');
        $store->set('baz', 'boom');
        $result = $store->flush();
        $this->assertTrue($result);
        $this->assertNull($store->get('foo'));
        $this->assertNull($store->get('baz'));
    }

    public function testScope()
    {
        $store = new ArrayStore();
        $store->scope(new Scope('foo'));
    }
}
