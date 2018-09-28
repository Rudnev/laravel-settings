<?php

namespace Rudnev\Settings\Tests\Integration\Commands;

use Mockery as m;
use Rudnev\Settings\SettingsManager;
use Illuminate\Support\Facades\Artisan;
use Rudnev\Settings\Tests\Integration\TestCase;

class ClearCacheTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app->singleton(SettingsManager::class, function ($app) {
            return m::mock(new SettingsManager($app));
        });
    }

    public function tearDown()
    {
        m::close();

        parent::tearDown();
    }

    public function testClearCache()
    {
        $mock = $this->app[SettingsManager::class];

        $mock->shouldReceive('clearCache');

        Artisan::call('settings:clear-cache');
    }
}
