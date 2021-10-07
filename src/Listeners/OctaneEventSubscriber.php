<?php

namespace Rudnev\Settings\Listeners;

use Illuminate\Container\Container;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;

class OctaneEventSubscriber
{
    /**
     * Set a fresh instance of the container.
     *
     * @return void
     */
    public function refresh()
    {
        $app = Container::getInstance();

        $app['settings']->setApplication($app);
    }

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
        $events->listen(RequestReceived::class, __CLASS__.'@refresh');
        $events->listen(RequestTerminated::class, __CLASS__.'@gc');
    }
}
