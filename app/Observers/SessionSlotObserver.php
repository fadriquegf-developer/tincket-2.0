<?php

namespace App\Observers;

use App\Models\SessionSlot;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Log;

class SessionSlotObserver
{
    public function saved(SessionSlot $sessionSlot)
    {
        try {
            $session = $sessionSlot->session;
            
            if (!$session) {
                Log::warning('SessionSlotObserver: No session found for SessionSlot', [
                    'session_slot_id' => $sessionSlot->id
                ]);
                return;
            }

            $redisService = new RedisSlotsService($session);
            
            if ($sessionSlot->status_id != null) {
                $redisService->lockSlot(
                    $sessionSlot->slot_id,
                    $sessionSlot->status_id,
                    $sessionSlot->comment
                );
            } else {
                $redisService->freeSlot($sessionSlot->slot_id);
            }
        } catch (\Exception $e) {
            Log::error('SessionSlotObserver: Error updating Redis', [
                'session_slot_id' => $sessionSlot->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleted(SessionSlot $sessionSlot)
    {
        try {
            $session = $sessionSlot->session;
            
            if (!$session) {
                return;
            }

            $redisService = new RedisSlotsService($session);
            $redisService->freeSlot($sessionSlot->slot_id);
        } catch (\Exception $e) {
            Log::error('SessionSlotObserver: Error freeing slot in Redis', [
                'session_slot_id' => $sessionSlot->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}