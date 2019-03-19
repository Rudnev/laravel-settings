<?php

namespace Rudnev\Settings\Tests\Unit;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Rudnev\Settings\SettingsManager;
use Rudnev\Settings\Stores\ArrayStore;
use Illuminate\Contracts\Cache\Factory;
use Rudnev\Settings\Cache\CacheDecorator;
use Rudnev\Settings\Stores\DatabaseStore;
use Illuminate\Contracts\Events\Dispatcher;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelCache;
use Rudnev\Settings\Contracts\RepositoryContract;

class SettingsManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testExceptionIfDefaultStoreIsNull()
    {
        $app = $this->getApp($this->getConfig());
        $app['config']['settings.default'] = null;
        $manager = new SettingsManager($app);

        $this->expectException(InvalidArgumentException::class);
        $manager->store();
    }

    public function testExceptionIfStoreIsNotDefined()
    {
        $app = $this->getApp($this->getConfig());
        $app['config']['settings.stores.foo.driver'] = 'qux';
        $manager = new SettingsManager($app);

        $this->expectException(InvalidArgumentException::class);

        $manager->store('foo');
    }

    public function testExceptionIfDriverIsNotSupported()
    {
        $app = $this->getApp($this->getConfig());
        $manager = new SettingsManager($app);

        $this->expectException(InvalidArgumentException::class);
        $manager->store('qux');
    }

    public function testStoreCanBeRetrieved()
    {
        $app = $this->getApp($this->getConfig());
        $manager = new SettingsManager($app);

        $this->assertEquals('foo', $manager->getDefaultStore());
        $repo = $manager->store();
        $this->assertInstanceOf(RepositoryContract::class, $repo);
        $this->assertInstanceOf(ArrayStore::class, $repo->getStore());
        $this->assertEquals('foo', $repo->getName());

        $repo = $manager->store('bar');
        $this->assertInstanceOf(CacheDecorator::class, $repo->getStore());
        $this->assertInstanceOf(DatabaseStore::class, $repo->getStore()->getStore());
        $this->assertEquals('bar', $repo->getName());

        $manager->store('foo');
    }

    public function testEventsCanBeDisabled()
    {
        $app = $this->getApp($this->getConfig());

        $manager = new SettingsManager($app);
        $this->assertEquals($app[Dispatcher::class], $manager->store('bar')->getEventDispatcher());

        $manager = new SettingsManager($app);
        $app['config']['settings.events'] = false;
        $this->assertNotEquals($app[Dispatcher::class], $manager->store('bar')->getEventDispatcher());
    }

    public function testCacheCanBeDisabled()
    {
        $app = $this->getApp($this->getConfig());

        $manager = new SettingsManager($app);
        $this->assertInstanceOf(CacheDecorator::class, $manager->store('bar')->getStore());

        $manager = new SettingsManager($app);
        $app['config']['settings.stores.bar.cache.enabled'] = false;
        $this->assertNotInstanceOf(CacheDecorator::class, $manager->store('bar')->getStore());
    }

    public function testCacheCanBeCleared()
    {
        $app = $this->getApp($this->getConfig());
        $manager = new SettingsManager($app);
        $store = $manager->store('bar')->getStore();
        $data = [];
        $this->bindData($store->getSecondLevelCache()->getStore(), $data);

        $store->getFirstLevelCache()->region('default')->put('foo', 'bar');
        $store->getSecondLevelCache()->region('default')->put('bar', 'baz');

        $this->assertEquals('bar', $store->getFirstLevelCache()->region('default')->get('foo'));
        $this->assertEquals('baz', $store->getSecondLevelCache()->region('default')->get('bar'));

        $manager->clearCache();

        $this->assertNull($store->getFirstLevelCache()->region('default')->get('foo'));
        $this->assertNull($store->getSecondLevelCache()->region('default')->get('bar'));
    }

    public function testCacheCompatibility()
    {
        $app = $this->getApp($this->getConfig());

        $app->shouldReceive('version')->once()->andReturn('5.8.5');
        $manager = new SettingsManager($app);
        $store = $manager->store('bar')->getStore();
        $this->assertEquals(60, $store->getSecondLevelCache()->getDefaultLifetime());

        $app->shouldReceive('version')->once()->andReturn('5.7.0');
        $manager = new SettingsManager($app);
        $store = $manager->store('bar')->getStore();
        $this->assertEquals(1, $store->getSecondLevelCache()->getDefaultLifetime());
    }

    public function testPreloadScopes()
    {
        $app = $this->getApp($this->getConfig());
        $manager = m::mock(new SettingsManager($app));
        $cacheRepo = $app[Factory::class]->store();
        $data = [];
        $this->bindData($cacheRepo, $data);
        $store = m::mock(new ArrayStore());
        $store->shouldReceive('all')->andReturn(['foo' => 'bar']);
        $store->shouldReceive('scope')->andReturn($store);
        $l1 = new FirstLevelCache();
        $l2 = new SecondLevelCache($cacheRepo);
        $decorator = new CacheDecorator($store, $l1, $l2);

        $this->assertFalse(array_search('bar', $data));

        $manager->preloadScopes(['default'], $decorator);

        $this->assertNotFalse(array_search('bar', $data));
    }

    public function testMagic()
    {
        $app = $this->getApp($this->getConfig());
        $manager = new SettingsManager($app);
        $repo = $manager->store();
        $this->assertEquals($repo->getStore(), $manager->getStore());
    }

    public function testCustomDriverClosureBoundObjectIsRepository()
    {
        $app = $this->getApp($this->getConfig());
        $app['config']['settings.stores.foo-store'] = ['driver' => 'bar-driver'];
        $manager = new SettingsManager($app);

        $repo = m::mock('Rudnev\Settings\Contracts\RepositoryContract');

        $manager->extend('bar-driver', function () use ($repo) {
            return $repo;
        });

        $this->assertEquals($repo, $manager->store('foo-store'));
    }

    protected function getConfig()
    {
        return new Repository([
            'settings' => [
                'default' => 'foo',
                'stores' => [
                    'foo' => [
                        'driver' => 'array',
                    ],
                    'bar' => [
                        'driver' => 'database',
                        'connection' => null,
                        'cache' => [
                            'enabled' => true,
                            'ttl' => 1,
                            'store' => null,
                        ],
                        'scopes' => [
                            'default' => 'default',
                            'preload' => [],
                        ],
                        'names' => [
                            'settings' => [
                                'table' => 'table',
                                'scope' => 'scope',
                                'key' => 'key',
                                'value' => 'value',
                            ],
                            'settings_models' => [
                                'table' => 'settings_models',
                                'entity' => 'model',
                                'key' => 'name',
                                'value' => 'value',
                            ],
                        ],
                    ],
                ],
                'events' => true,
            ],
        ]);
    }

    protected function getApp($config)
    {
        $app = m::spy('Illuminate\Foundation\Application');

        $db = m::spy('stdClass');
        $db->shouldReceive('connection')->andReturn(m::spy('Illuminate\Database\ConnectionInterface'));
        $app->shouldReceive('offsetGet')->with('db')->andReturn($db);

        $app->shouldReceive('bound')->with(Dispatcher::class)->andReturnTrue();
        $app->shouldReceive('offsetGet')->with('config')->andReturn($config);
        $app->shouldReceive('offsetGet')->with(Dispatcher::class)->andReturn(m::spy('Illuminate\Events\Dispatcher'));

        $app->shouldReceive('bound')->with(Factory::class)->andReturnTrue();
        $cacheManager = m::spy('Illuminate\Contracts\Cache\Factory');
        $cacheRepo = m::spy('Illuminate\Contracts\Cache\Repository');
        $cacheManager->shouldReceive('store')->andReturn($cacheRepo);
        $app->shouldReceive('offsetGet')->with(Factory::class)->andReturn($cacheManager);

        return $app;
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
