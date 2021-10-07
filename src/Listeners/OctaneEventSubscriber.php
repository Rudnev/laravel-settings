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
     * @return array
     */
    public function subscribe()
    {
        return [
            RequestTerminated::class => 'gc',
        ];
    }
}
