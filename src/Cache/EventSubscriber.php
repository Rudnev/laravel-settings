<?php

namespace Rudnev\Settings\Cache;

use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;
use Rudnev\Settings\Events\AllSettingsRemoved;

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
     * The PropertyWritten event handler.
     *
     * @param \Rudnev\Settings\Events\PropertyWritten $event
     * @return void
     */
    public function propertyWritten(PropertyWritten $event)
    {
        if (is_null($event->getScope())) {
            $this->cache->set($event->key, $event->value);
        }
    }

    /**
     * The PropertyRemoved event handler.
     *
     * @param \Rudnev\Settings\Events\PropertyRemoved $event
     * @return void
     */
    public function propertyRemoved(PropertyRemoved $event)
    {
        if (is_null($event->getScope())) {
            $this->cache->forget($event->key);
        }
    }

    /**
     * The AllSettingsRemoved event handler.
     *
     * @param \Rudnev\Settings\Events\AllSettingsRemoved $event
     * @return void
     */
    public function allSettingsRemoved(AllSettingsRemoved $event)
    {
        if (is_null($event->getScope())) {
            $this->cache->flush();
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(PropertyWritten::class, [$this, 'propertyWritten']);
        $events->listen(PropertyRemoved::class, [$this, 'propertyRemoved']);
        $events->listen(AllSettingsRemoved::class, [$this, 'allSettingsRemoved']);
    }
}
