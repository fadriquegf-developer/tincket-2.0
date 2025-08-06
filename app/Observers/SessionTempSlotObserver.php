<?php

namespace App\Observers;

use App\Models\SessionTempSlot;

class SessionTempSlotObserver
{

    public function saved(SessionTempSlot $sessionTempSlot)
    {
        if($sessionTempSlot->status_id != null)
        {
            (new \App\Services\Api\SlotCacheService($sessionTempSlot->session()->first()))->lockSlot($sessionTempSlot->slot()->first(), $sessionTempSlot->status->id, null, $sessionTempSlot->cart_id);
        }

        if($sessionTempSlot->status_id == null)
        {
            (new \App\Services\Api\SlotCacheService($sessionTempSlot->session()->first))->freeSlot($sessionTempSlot->slot()->first());
        }
    }

    public function deleted(SessionTempSlot $sessionTempSlot)
    {
        (new \App\Services\Api\SlotCacheService($sessionTempSlot->session()->first()))->freeSlot($sessionTempSlot->slot()->first());
    }

}
