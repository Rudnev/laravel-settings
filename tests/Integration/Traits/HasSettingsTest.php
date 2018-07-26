<?php

namespace Rudnev\Settings\Tests\Integration\Traits;

use Settings;
use Rudnev\Settings\Traits\HasSettings;
use Rudnev\Settings\Tests\Integration\TestCase;

class HasSettingsTest extends TestCase
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

    public function testScope()
    {
        settings()->set('app-settings', 'foo');
        $model = $this->getModel();
        $this->assertNull($model->settings('app-settings'));
        $this->assertEquals('foo', settings()->get('app-settings'));

        $model->settings()->set('user-settings', 'baz');
        $this->assertEquals('baz', $model->settings()->get('user-settings'));
        $this->assertNull(settings('user-settings'));

        $model->settings(['foo' => 1, 'bar' => 2]);
        $this->assertEquals($model->settings('foo'), 1);
    }

    protected function getModel()
    {
        return new class {
            use HasSettings;

            public function getKey()
            {
                return 123;
            }
        };
    }
}
