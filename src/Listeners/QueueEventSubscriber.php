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
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(JobProcessed::class, __CLASS__.'@gc');
        $events->listen(JobFailed::class, __CLASS__.'@gc');
    }
}
