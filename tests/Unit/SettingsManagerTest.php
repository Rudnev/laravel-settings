<?php

namespace Rudnev\Settings\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\SettingsManager;

class SettingsManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCustomDriverClosureBoundObjectIsRepository()
    {
        $settingsManager = new SettingsManager([
            'config' => [
                'settings.stores.' . __CLASS__ => [
                    'driver' => __CLASS__,
                ],
            ]
        ]);

        $store = m::mock('Rudnev\Settings\Contracts\StoreContract');
        $repo = m::mock('Rudnev\Settings\Contracts\RepositoryContract');

        $repo->shouldReceive('getStore')->andReturn($store);
        $store->shouldReceive('setName')->with(__CLASS__);

        $settingsManager->extend(__CLASS__, function () use ($repo) {
            return $repo;
        });

        $this->assertEquals($repo, $settingsManager->store(__CLASS__));
    }
}