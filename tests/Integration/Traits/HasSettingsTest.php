<?php

namespace Rudnev\Settings\Tests\Integration\Traits;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mockery as m;
use Rudnev\Settings\Structures\Container;
use Rudnev\Settings\Tests\Integration\TestCase;
use Rudnev\Settings\Traits\HasSettings;

class HasSettingsTest extends TestCase
{
    protected function setUp(): void
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

    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testScope()
    {
        settings()->set('app-settings', 'foo');
        $model = new Model();
        $this->assertNull($model->settings('app-settings'));
        $this->assertEquals('foo', settings()->get('app-settings'));

        $model->settings()->set('user-settings', 'baz');
        $this->assertEquals('baz', $model->settings()->get('user-settings'));
        $this->assertNull(settings('user-settings'));

        $model->settings(['foo' => 1, 'bar' => 2]);
        $this->assertEquals($model->settings('foo'), 1);

        $this->assertEquals('qux-default', $model->settings('qux'));
    }

    public function testValuesCanBeSetAndRetrieved()
    {
        $model = new Model();
        $model->exists = true;
        $this->assertInstanceOf(Container::class, $model->getSettingsAttribute());
        $model = new Model();
        $model->exists = true;
        $model->setSettingsAttribute(['foo' => 'bar']);
        $this->assertEquals('bar', $model->settings['foo']);
        $this->assertEquals(['foo' => 'bar', 'qux' => 'qux-default'], $model->settings->toArray());

        $model = new Model();
        $model->settings['bar'] = 'baz';
        $this->assertEquals('baz', $model->settings['bar']);
        $model->settings = null;
        $this->assertNull($model->settings['bar']);

        $model = new Model();
        $model->settings['example.com'] = 'baz';
        $model->settings['example'] = ['com' => 'qux'];
        $model->event('saved');
        $this->assertEquals('qux', $model->settings['example.com']);
        $this->assertEquals('qux', $model->settings['example']['com']);
    }

    public function testEvents()
    {
        $settings = m::mock();

        $model = m::mock(Model::class.'[settings]');
        $model->shouldNotReceive('settings');
        $model->event('saved');

        $model->shouldReceive('settings')->once()->andReturn($settings);
        $settings->shouldReceive('set')->once();
        $model->setSettingsAttribute(['foo' => 'bar']);
        $model->event('saved');

        $model->shouldReceive('settings')->twice()->andReturn($settings);
        $settings->shouldReceive('forget')->once();
        $settings->shouldReceive('set')->once();
        $model->setSettingsAttribute(['bar' => 'baz']);
        $model->event('saved');

        $model->shouldReceive('settings')->once()->andReturn($settings);
        $settings->shouldReceive('flush');
        $model->event('deleting');

        $model = m::mock(SoftDeletesModel::class.'[settings]');
        $model->shouldNotReceive('settings');
        $settings->shouldNotReceive('flush');
        $model->event('deleting');
    }
}

class Model extends EloquentModel
{
    use HasSettings;

    protected $settingsConfig = [
        'default' => [
            'qux' => 'qux-default',
        ],
    ];

    public function event($event)
    {
        $this->fireModelEvent($event);
    }
}

class SoftDeletesModel extends Model
{
    use SoftDeletes;
}
