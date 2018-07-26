<?php

namespace Rudnev\Settings\Tests\Integration;

use Rudnev\Settings\ServiceProvider;
use Rudnev\Settings\Facades\SettingsFacade;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Settings' => SettingsFacade::class,
        ];
    }
}
