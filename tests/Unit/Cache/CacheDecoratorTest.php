<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Stores\ArrayStore;
use Rudnev\Settings\Cache\CacheDecorator;
use Illuminate\Contracts\Cache\Repository;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelRegion;

class CacheDecoratorTest extends TestCase
{
    public function setUp()
    {
        SecondLevelCache::reset();
        SecondLevelRegion::reset();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testStoreCanBeSetAndRetrieved()
    {
        $store = m::spy(StoreContract::class);
        $l1 = m::spy(FirstLevelCache::class);
        $l2 = m::spy(SecondLevelCache::class);

        $decorator = new CacheDecorator($store, $l1, $l2);
        $this->assertEquals(spl_object_id($store), spl_object_id($decorator->getStore()));

        $decorator->setStore($store2 = clone $store);
        $this->assertEquals(spl_object_id($store2), spl_object_id($decorator->getStore()));
    }

    public function testL1CacheCanBeSetAndRetrieved()
    {
        $store = m::spy(StoreContract::class);
        $l1 = m::spy(FirstLevelCache::class);
        $l2 = m::spy(SecondLevelCache::class);

        $decorator = new CacheDecorator($store, $l1, $l2);
        $this->assertEquals(spl_object_id($l1), spl_object_id($decorator->getFirstLevelCache()));

        $decorator->setFirstLevelCache($clone = clone $l1);
        $this->assertEquals(spl_object_id($clone), spl_object_id($decorator->getFirstLevelCache()));
    }

    public function testL2CacheCanBeSetAndRetrieved()
    {
        $store = m::spy(StoreContract::class);
        $l1 = m::spy(FirstLevelCache::class);
        $l2 = m::spy(SecondLevelCache::class);

        $decorator = new CacheDecorator($store, $l1, $l2);
        $this->assertEquals(spl_object_id($l2), spl_object_id($decorator->getSecondLevelCache()));

        $decorator->setSecondLevelCache($clone = clone $l2);
        $this->assertEquals(spl_object_id($clone), spl_object_id($decorator->getSecondLevelCache()));
    }

    public function testNameCanBeSetAndRetrieved()
    {
        $store = m::spy(StoreContract::class);
        $l1 = m::spy(FirstLevelCache::class);
        $l2 = m::spy(SecondLevelCache::class);

        $decorator = new CacheDecorator($store, $l1, $l2);
        $store->shouldReceive('getName')->andReturn('foo');
        $this->assertEquals('foo', $decorator->getName());

        $store->shouldReceive('setName')->with('bar');
        $decorator->setName('bar');
    }

    public function testScopeCanBeSetAndRetrieved()
    {
        $store = m::spy(StoreContract::class);
        $l1 = m::spy(FirstLevelCache::class);
        $l2 = m::spy(SecondLevelCache::class);
        $scope = new Scope('foo');
        $scope2 = new Scope('bar');
        $scope3 = new Scope('baz');

        $decorator = new CacheDecorator($store, $l1, $l2);
        $store->shouldReceive('getScope')->andReturn($scope);
        $this->assertEquals(spl_object_id($scope), spl_object_id($decorator->getScope()));

        $store->shouldReceive('setName')->with($scope2);
        $decorator->setScope($scope2);

        $store->shouldReceive('scope')->with($scope3);
        $decorator2 = $decorator->scope($scope3);
        $this->assertNotEquals(spl_object_id($decorator), spl_object_id($decorator2));
    }

    public function testExistenceChecking()
    {
        $storeMock = m::mock($store = new ArrayStore());
        $l1Mock = m::mock($l1 = new FirstLevelCache());
        $l2Mock = m::mock($l2 = new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $l1Region = function ($name) use ($l1) {
            return $l1->region($name);
        };
        $l2Region = function ($name) use ($l2) {
            return $l2->region($name);
        };
        $storeGet = function ($key) use ($store) {
            return $store->get($key);
        };

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(1)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('get')->times(1)->andReturnUsing($storeGet);
        $this->assertFalse($decorator->has('foo'));

        $l1Mock->shouldReceive('region')->zeroOrMoreTimes()->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->zeroOrMoreTimes()->andReturnUsing($l2Region);
        $storeMock->shouldReceive('get')->zeroOrMoreTimes()->andReturnUsing($storeGet);
        $decorator->set('foo', false);
        $this->assertTrue($decorator->has('foo'));
    }

    public function testItemCanBePutAndRetrieved()
    {
        $storeMock = m::mock($store = new ArrayStore());
        $l1Mock = m::mock($l1 = new FirstLevelCache());
        $l2Mock = m::mock($l2 = new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $l1Region = function ($name) use ($l1) {
            return $l1->region($name);
        };
        $l2Region = function ($name) use ($l2) {
            return $l2->region($name);
        };
        $storeGet = function ($key) use ($store) {
            return $store->get($key);
        };
        $storeSet = function ($key, $value) use ($store) {
            $store->set($key, $value);
        };

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(2)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('set')->times(1)->andReturnUsing($storeSet);
        $decorator->set('foo', 'bar');

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldNotReceive('region');
        $storeMock->shouldNotReceive('get');
        $this->assertEquals('bar', $decorator->get('foo'));

        $l1Mock->flush();
        $l2Mock->flush();

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(1)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('get')->times(1)->andReturnUsing($storeGet);
        $this->assertEquals('bar', $decorator->get('foo'));
    }

    public function testMultipleItemsCanBePutAndRetrieved()
    {
        $storeMock = m::mock($store = new ArrayStore());
        $l1Mock = m::mock($l1 = new FirstLevelCache());
        $l2Mock = m::mock($l2 = new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $l1Region = function ($name) use ($l1) {
            return $l1->region($name);
        };
        $l2Region = function ($name) use ($l2) {
            return $l2->region($name);
        };
        $storeGetMultiple = function ($keys) use ($store) {
            return $store->getMultiple($keys);
        };
        $storeSetMultiple = function ($values) use ($store) {
            $store->setMultiple($values);
        };

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(2)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('setMultiple')->times(1)->andReturnUsing($storeSetMultiple);
        $decorator->setMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldNotReceive('region');
        $storeMock->shouldNotReceive('getMultiple');
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $decorator->getMultiple(['foo', 'bar', 'baz']));

        $l1Mock->flush();
        $l2Mock->flush();

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(1)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('getMultiple')->times(1)->andReturnUsing($storeGetMultiple);
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $decorator->getMultiple(['foo', 'bar', 'baz']));
    }

    public function testAllItemsCanBeRetrieved()
    {
        $storeMock = m::mock($store = new ArrayStore());
        $l1Mock = m::mock($l1 = new FirstLevelCache());
        $l2Mock = m::mock($l2 = new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $l1Region = function ($name) use ($l1) {
            return $l1->region($name);
        };
        $l2Region = function ($name) use ($l2) {
            return $l2->region($name);
        };
        $storeAll = function () use ($store) {
            return $store->all();
        };

        $decorator->setMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(1)->andReturnUsing($l2Region);
        $storeMock->shouldReceive('all')->times(1)->andReturnUsing($storeAll);
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $decorator->all());

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldNotReceive('region');
        $storeMock->shouldNotReceive('all');
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $decorator->all());

        $l1Mock->flush();

        $l1Mock->shouldReceive('region')->times(1)->andReturnUsing($l1Region);
        $l2Mock->shouldReceive('region')->times(1)->andReturnUsing($l2Region);
        $storeMock->shouldNotReceive('all');
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $decorator->all());
    }

    public function testItemCanBeRemoved()
    {
        $storeMock = m::mock(new ArrayStore());
        $l1Mock = m::mock(new FirstLevelCache());
        $l2Mock = m::mock(new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $decorator->setMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);
        $decorator->forget('bar');
        $this->assertNull($decorator->get('bar'));
        $this->assertEquals(['foo' => 123, 'baz' => 132], $decorator->all());
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $storeMock = m::mock(new ArrayStore());
        $l1Mock = m::mock(new FirstLevelCache());
        $l2Mock = m::mock(new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $decorator->setMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);
        $decorator->forgetMultiple(['foo', 'baz']);
        $this->assertNull($decorator->get('foo'));
        $this->assertEquals(['bar' => 321], $decorator->all());
    }

    public function testAllItemsCanBeRemoved()
    {
        $storeMock = m::mock(new ArrayStore());
        $l1Mock = m::mock(new FirstLevelCache());
        $l2Mock = m::mock(new SecondLevelCache($cacheRepo = m::spy(Repository::class)));
        $decorator = new CacheDecorator($storeMock, $l1Mock, $l2Mock);
        $data = [];
        $this->bindData($cacheRepo, $data);

        $decorator->setMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);
        $decorator->flush();
        $this->assertNull($decorator->get('foo'));
        $this->assertEquals([], $decorator->all());
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
            return array_only($data, $keys);
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

        $store->shouldReceive('flush')->andReturnUsing(function ($keys) use (&$data) {
            $data = [];
        });
    }
}
