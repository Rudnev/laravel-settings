<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\EventSubscriber;
use Rudnev\Settings\Events\AllSettingsRemoved;
use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;

class EventSubscriberTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSubscribe()
    {
        $class = EventSubscriber::class;
        $subscriber = new $class($this->getCache());
        $dispatcher = m::spy('Illuminate\Events\Dispatcher');
        $dispatcher->shouldReceive('listen')->with(PropertyWritten::class, $class.'@propertyWritten');
        $dispatcher->shouldReceive('listen')->with(PropertyRemoved::class, $class.'@propertyWritten');
        $dispatcher->shouldReceive('listen')->with(AllSettingsRemoved::class, $class.'@propertyWritten');
        $subscriber->subscribe($dispatcher);
    }

    public function testPropertyWritten()
    {
        $cache = $this->getCache();
        $subscriber = new EventSubscriber($cache);
        $cache->shouldReceive('set')->with('foo', 'bar');
        $subscriber->propertyWritten(new PropertyWritten('foo', 'bar'));
    }

    public function testPropertyRemoved()
    {
        $cache = $this->getCache();
        $subscriber = new EventSubscriber($cache);
        $cache->shouldReceive('forget')->with('foo');
        $subscriber->propertyRemoved(new PropertyRemoved('foo'));
    }

    public function testAllSettingsRemoved()
    {
        $cache = $this->getCache();
        $subscriber = new EventSubscriber($cache);
        $cache->shouldReceive('flush');
        $subscriber->allSettingsRemoved(new AllSettingsRemoved());
    }

    protected function getCache()
    {
        return m::mock('Rudnev\Settings\Cache\Cache');
    }
}