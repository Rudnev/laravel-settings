<?php

namespace Rudnev\Settings\Tests\Integration\Commands;

use Illuminate\Support\Facades\Artisan;
use Mockery as m;
use Rudnev\Settings\SettingsManager;
use Rudnev\Settings\Tests\Integration\TestCase;

class ClearCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(SettingsManager::class, function ($app) {
            return m::mock(new SettingsManager($app));
        });
    }

    protected function tearDown(): void
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
