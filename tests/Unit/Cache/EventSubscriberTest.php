<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\EventSubscriber;
use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;
use Rudnev\Settings\Events\AllSettingsRemoved;

class EventSubscriberTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSubscribe()
    {
        $subscriber = new EventSubscriber($this->getCache());
        $dispatcher = m::spy('Illuminate\Events\Dispatcher');
        $dispatcher->shouldReceive('listen')->with(PropertyWritten::class, [$subscriber, 'propertyWritten']);
        $dispatcher->shouldReceive('listen')->with(PropertyRemoved::class, [$subscriber, 'propertyWritten']);
        $dispatcher->shouldReceive('listen')->with(AllSettingsRemoved::class, [$subscriber, 'propertyWritten']);
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
