<?php

namespace Rudnev\Settings\Listeners;

use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;

class OctaneEventSubscriber
{
    /**
     * Set a fresh container instance.
     *
     * @param  \Laravel\Octane\Events\RequestReceived  $event
     * @return void
     */
    public function refresh(RequestReceived $event)
    {
        $event->sandbox['settings']->setApplication($event->sandbox);
    }

    /**
     * Cleanup.
     *
     * @param  \Laravel\Octane\Events\RequestTerminated  $event
     * @return void
     */
    public function gc(RequestTerminated $event)
    {
        $event->sandbox['settings']->gc();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(RequestReceived::class, __CLASS__.'@refresh');
        $events->listen(RequestTerminated::class, __CLASS__.'@gc');
    }
}
