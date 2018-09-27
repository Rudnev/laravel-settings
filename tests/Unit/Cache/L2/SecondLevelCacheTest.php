<?php

namespace Rudnev\Settings\Tests\Unit\Cache\L2;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\L2\SecondLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelRegion;

class SecondLevelCacheTest extends TestCase
{
    public function setUp()
    {
        SecondLevelCache::reset();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCacheStoreCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $cache = new SecondLevelCache($store);
        $this->assertEquals(spl_object_id($store), spl_object_id($cache->getStore()));

        $cache->setStore($store2 = clone $store);
        $this->assertEquals(spl_object_id($store2), spl_object_id($cache->getStore()));
    }

    public function testPrefixCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $cache = new SecondLevelCache($store);
        $this->assertEquals('laravel_settings', $cache->getPrefix());

        $cache->setPrefix('foo');
        $this->assertEquals('foo', $cache->getPrefix());
    }

    public function testDefaultLifetimeCanBeSetAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');

        $cache = new SecondLevelCache($store);
        $this->assertEquals(120, $cache->getDefaultLifetime());

        $cache->setDefaultLifetime(300);
        $this->assertEquals(300, $cache->getDefaultLifetime());
    }

    public function testRegionCanBeCreatedAndRetrieved()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $cache = new SecondLevelCache($store);

        $region = $cache->region('foo');
        $this->assertInstanceOf(SecondLevelRegion::class, $region);
        $this->assertEquals('laravel_settings[0].foo', $region->getName());
        $this->assertEquals(spl_object_id($store), spl_object_id($region->getStore()));
        $this->assertEquals(120, $region->getLifetime());
        $this->assertEquals(spl_object_id($region), spl_object_id($cache->region('foo')));
    }

    public function testCacheCanBeFlushed()
    {
        $store = m::spy('\Illuminate\Contracts\Cache\Repository');
        $data = [];
        $this->bindData($store, $data);
        $cache = new SecondLevelCache($store);
        $key = 'bar';
        $value = 123;

        $cache->region('foo')->put($key, $value);
        $this->assertEquals($value, $cache->region('foo')->get($key));

        $cache->flush();
        $this->assertNull($cache->region('foo')->get($key));
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

        $store->shouldReceive('forever')->andReturnUsing(function ($key, $value) use (&$data) {
            $data[$key] = $value;
        });
    }
}
