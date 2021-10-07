<?php

namespace Rudnev\Settings\Listeners;

use Illuminate\Container\Container;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class QueueEventSubscriber
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
            JobProcessed::class => 'gc',
            JobFailed::class => 'gc',
        ];
    }
}
