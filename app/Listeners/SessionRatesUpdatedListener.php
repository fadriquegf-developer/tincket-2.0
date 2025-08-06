<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SessionRatesUpdatedListener implements ShouldQueue
{

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(\App\Events\SessionRatesUpdated $event)
    {
        (new \App\Services\Api\SlotCacheService($event->session))->regenerateCache();
    }

}

