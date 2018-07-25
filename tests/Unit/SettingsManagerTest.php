<?php

namespace Rudnev\Settings\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Contracts\RepositoryContract;
use Rudnev\Settings\SettingsManager;
use Rudnev\Settings\Stores\ArrayStore;
use Rudnev\Settings\Stores\DatabaseStore;

class SettingsManagerTest extends TestCase
{
    public function tearDown()
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
        $this->assertInstanceOf(DatabaseStore::class, $repo->getStore());
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
        $this->assertNotNull($manager->store('bar')->getCache());

        $manager = new SettingsManager($app);
        $app['config']['settings.stores.bar.cache.enabled'] = false;
        $this->assertNull($manager->store('bar')->getCache());
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
        $cacheManager->shouldReceive('store')->andReturn(m::spy('Illuminate\Contracts\Cache\Repository'));
        $app->shouldReceive('offsetGet')->with(Factory::class)->andReturn($cacheManager);

        return $app;
    }
}