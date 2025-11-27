<?php

namespace App\Observers;

use App\Models\SessionTempSlot;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Log;

class SessionTempSlotObserver
{
    public function saved(SessionTempSlot $sessionTempSlot)
    {
        try {
            $session = $sessionTempSlot->session;

            if (!$session) {
                Log::warning('SessionTempSlotObserver: No session found', [
                    'session_temp_slot_id' => $sessionTempSlot->id
                ]);
                return;
            }

            $slot = $sessionTempSlot->slot;

            if (!$slot) {
                Log::warning('SessionTempSlotObserver: No slot found', [
                    'session_temp_slot_id' => $sessionTempSlot->id
                ]);
                return;
            }

            $redisService = new RedisSlotsService($session);

            if ($sessionTempSlot->status_id != null) {
                $redisService->lockSlot(
                    $slot->id,
                    $sessionTempSlot->status_id,
                    null,
                    $sessionTempSlot->cart_id
                );
            } else {
                $redisService->freeSlot($slot->id);
            }
        } catch (\Exception $e) {
            Log::error('SessionTempSlotObserver: Error updating Redis', [
                'session_temp_slot_id' => $sessionTempSlot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleted(SessionTempSlot $sessionTempSlot)
    {
        try {
            $session = $sessionTempSlot->session;

            if (!$session) {
                return;
            }

            $slot = $sessionTempSlot->slot;

            if (!$slot) {
                return;
            }

            $redisService = new RedisSlotsService($session);
            $redisService->freeSlot($slot->id);
        } catch (\Exception $e) {
            Log::error('SessionTempSlotObserver: Error freeing slot in Redis', [
                'session_temp_slot_id' => $sessionTempSlot->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
