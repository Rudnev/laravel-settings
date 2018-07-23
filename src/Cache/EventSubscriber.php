<?php

namespace Rudnev\Settings\Cache;

use Rudnev\Settings\Events\AllSettingsRemoved;
use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;

class EventSubscriber
{
    /**
     * The cache instance.
     *
     * @var \Rudnev\Settings\Cache\Cache
     */
    protected $cache;

    /**
     * EventListener constructor.
     *
     * @param \Rudnev\Settings\Cache\Cache $cache
     * @return void
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * The PropertyWritten event handler
     *
     * @param \Rudnev\Settings\Events\PropertyWritten $event
     * @return void
     */
    public function propertyWritten(PropertyWritten $event)
    {
        $this->cache->set($event->key, $event->value);
    }

    /**
     * The PropertyRemoved event handler
     *
     * @param \Rudnev\Settings\Events\PropertyRemoved $event
     * @return void
     */
    public function propertyRemoved(PropertyRemoved $event)
    {
        $this->cache->forget($event->key);
    }

    /**
     * The AllSettingsRemoved event handler
     *
     * @param \Rudnev\Settings\Events\AllSettingsRemoved $event
     * @return void
     */
    public function allSettingsRemoved(AllSettingsRemoved $event)
    {
        $this->cache->flush();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(PropertyWritten::class, __CLASS__.'@propertyWritten');
        $events->listen(PropertyRemoved::class, __CLASS__.'@propertyRemoved');
        $events->listen(AllSettingsRemoved::class, __CLASS__.'@allSettingsRemoved');
    }
}