<?php

namespace Rudnev\Settings\Tests\Integration;

use Settings;
use Rudnev\Settings\Repository;
use Rudnev\Settings\ServiceProvider;
use Rudnev\Settings\SettingsManager;
use Rudnev\Settings\Contracts\FactoryContract;
use Rudnev\Settings\Contracts\RepositoryContract;

class ServiceProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set([
            'settings' => [
                'default' => 'foo',
                'stores' => [
                    'foo' => [
                        'driver' => 'array',
                    ],
                ],
                'events' => true,
            ],
        ]);
    }

    public function testBindings()
    {
        $this->assertInstanceOf(SettingsManager::class, $this->app['settings']);
        $this->assertInstanceOf(SettingsManager::class, $this->app[FactoryContract::class]);
        $this->assertInstanceOf(SettingsManager::class, $this->app[SettingsManager::class]);

        $this->assertInstanceOf(Repository::class, $this->app['settings.store']);
        $this->assertInstanceOf(Repository::class, $this->app[RepositoryContract::class]);
        $this->assertInstanceOf(Repository::class, $this->app[Repository::class]);

        $provider = new ServiceProvider($this->app);
        $this->assertEquals([
            'settings',
            'settings.store',
            FactoryContract::class,
            SettingsManager::class,
            RepositoryContract::class,
            Repository::class,
        ], $provider->provides());
    }

    public function testHelperFunction()
    {
        settings(['foo' => 'bar']);
        $this->assertEquals('bar', settings('foo'));

        settings()->set('bar', 'baz');
        $this->assertEquals('baz', settings()->get('bar'));
    }

    public function testFacade()
    {
        Settings::set('foo', 'bar');
        $this->assertEquals('bar', Settings::get('foo'));

        Settings::set([
            'foo' => 'bar',
            'qux' => 'baz',
        ]);
        $this->assertEquals([
            'foo' => 'bar',
            'bar' => null,
            'qux' => 'baz',
        ], Settings::get(['foo', 'bar', 'qux']));
    }
}
