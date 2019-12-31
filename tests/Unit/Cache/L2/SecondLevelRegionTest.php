<?php

namespace Rudnev\Settings\Tests\Unit\Cache\L2;

use Mockery as m;
use Illuminate\Support\Arr;
use Illuminate\Cache\Lock;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\L2\SecondLevelRegion;

class SecondLevelRegionTest extends TestCase
{
    protected function setUp(): void
    {
        SecondLevelRegion::reset();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testNameCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $region = new SecondLevelRegion('foo', $store);
        $this->assertEquals('foo', $region->getName());

        $region->setName('bar');
        $this->assertEquals('bar', $region->getName());
    }

    public function testCacheStoreCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $region = new SecondLevelRegion('foo', $store);
        $this->assertEquals(spl_object_id($store), spl_object_id($region->getStore()));

        $region->setStore($store2 = clone $store);
        $this->assertEquals(spl_object_id($store2), spl_object_id($region->getStore()));
    }

    public function testLifetimeCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $region = new SecondLevelRegion('foo', $store);
        $this->assertEquals(0, $region->getLifetime());

        $region->setLifetime(300);
        $this->assertEquals(300, $region->getLifetime());
    }

    public function testExistenceChecking()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);

        $region = new SecondLevelRegion('foo', $store);
        $this->assertFalse($region->has('foo'));

        $region->put('foo', false);
        $this->assertTrue($region->has('foo'));
    }

    public function testItemCanBePutAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);

        $region = new SecondLevelRegion('foo', $store);
        $this->assertNull($region->get('foo'));

        $region->put('bar', 'baz');
        $this->assertEquals('baz', $region->get('bar'));

        $this->assertEquals(123, $region->get('baz', function () {
            return 123;
        }));
        $this->assertEquals(123, $region->get('baz'));
    }

    public function testMultipleItemsCanBePutAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);
        $region = new SecondLevelRegion('foo', $store);

        $region->putMultiple(['foo' => 123, 'bar' => 321]);
        $this->assertEquals(123, $region->get('foo'));
        $this->assertEquals(321, $region->get('bar'));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => null], $region->getMultiple(['foo', 'bar', 'baz']));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $region->getMultiple([
            'foo',
            'bar',
            'baz',
        ], function ($keys) {
            $data = [
                'baz' => 132,
            ];

            return Arr::only($data, $keys);
        }));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $region->getMultiple(['foo', 'bar', 'baz']));
    }

    public function testItemCanBeRemoved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);
        $region = new SecondLevelRegion('foo', $store);

        $region->put('bar', 'baz');
        $region->forget('bar');
        $this->assertNull($region->get('bar'));
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);
        $region = new SecondLevelRegion('foo', $store);
        $region->putMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);

        $region->forgetMultiple(['bar', 'baz']);
        $this->assertEquals(['foo' => 123, 'bar' => null, 'baz' => null], $region->getMultiple(['foo', 'bar', 'baz']));
    }

    public function testAllItemsCanBeRemoved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);
        $region = new SecondLevelRegion('foo', $store);

        $region->put('bar', 'baz');
        $region->put('baz', 'qux');
        $region->flush();
        $this->assertNull($region->get('bar'));
        $this->assertNull($region->get('baz'));
    }

    public function testLock()
    {
        $repo = m::spy('\Illuminate\Contracts\Cache\Repository');

        $region = new SecondLevelRegion('foo', $repo);
        $called = false;
        $repo->shouldNotReceive('lock');
        $region->lock('foo', function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);

        $region = new SecondLevelRegion('foo', $repo);
        $repo->shouldReceive('getStore')->once()->andReturn(new class {
            protected function lock()
            {
            }
        });
        $repo->shouldReceive('lock')->once()->andReturn($lock = m::spy(Lock::class));
        $lock->shouldReceive('block')->andReturnUsing(function () {
            value(func_get_arg(1));
        });
        $called = false;
        $region->lock('foo', function () use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    protected function bindData(m\MockInterface $store, array &$data)
    {
        $store->shouldReceive('has')->andReturnUsing(function ($key) use (&$data) {
            return array_key_exists($key, $data);
        });

        $store->shouldReceive('get')->andReturnUsing(function ($key) use (&$data) {
            return $data[$key] ?? null;
        });

        $store->shouldReceive('getMultiple')->andReturnUsing(function ($keys) use (&$data) {
            return Arr::only($data, $keys);
        });

        $store->shouldReceive('put')->andReturnUsing(function ($key, $value) use (&$data) {
            $data[$key] = $value;
        });

        $store->shouldReceive('setMultiple')->andReturnUsing(function ($values) use (&$data) {
            $data = array_merge($data, $values);
        });

        $store->shouldReceive('forget')->andReturnUsing(function ($key) use (&$data) {
            unset($data[$key]);
        });

        $store->shouldReceive('forever')->andReturnUsing(function ($key, $value) use (&$data) {
            $data[$key] = $value;
        });
    }
}
