<?php

namespace Rudnev\Settings\Listeners;

use Illuminate\Container\Container;
use Laravel\Octane\Events\RequestTerminated;

class OctaneEventSubscriber
{
    /**
     * Cleanup.
     *
     * @return void
     */
    public function gc()
    {
        Container::getInstance()['settings']->gc();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(RequestTerminated::class, __CLASS__.'@gc');
    }
}
