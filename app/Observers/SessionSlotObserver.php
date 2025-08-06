<?php

namespace App\Observers;

use App\Models\SessionSlot;

class SessionSlotObserver
{

    public function saved(SessionSlot $sessionSlot)
    {
        if($sessionSlot->status_id != null)
        {
            (new \App\Services\Api\SlotCacheService($sessionSlot->session()->first()))->lockSlot($sessionSlot->slot->first(), $sessionSlot->status->id, $sessionSlot->comment);
        }

        if($sessionSlot->status_id == null)
        {
            (new \App\Services\Api\SlotCacheService($sessionSlot->session()->first()))->freeSlot($sessionSlot->slot()->first(), $sessionSlot->comment);
        }
    }

    public function deleted(SessionSlot $sessionSlot)
    {
        (new \App\Services\Api\SlotCacheService($sessionSlot->session()->first()))->freeSlot($sessionSlot->slot()->first(), $sessionSlot->comment);
    }

}
