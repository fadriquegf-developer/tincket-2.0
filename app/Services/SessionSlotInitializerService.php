<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Slot;
use App\Models\SessionSlot;
use App\Models\Space;
use App\Services\RedisSlotsService;

class SessionSlotInitializerService
{
    /**
     * Inicializa los SessionSlots desde los Slots del Space
     */
    public function initialize(Session $session, ?Space $space = null): void
    {
        if (!$session->is_numbered) {
            return;
        }

        $space = $space ?: $session->space;

        if (!$space) {
            return;
        }

        SessionSlot::where('session_id', $session->id)->delete();

        $slots = Slot::where('space_id', $space->id)
            ->whereNotNull('status_id')
            ->get(['id', 'status_id', 'comment']);

        if ($slots->isEmpty()) {
            return;
        }

        $now = now();
        $data = [];

        foreach ($slots as $slot) {
            $data[] = [
                'session_id' => $session->id,
                'slot_id'    => $slot->id,
                'status_id'  => $slot->status_id,
                'comment'    => $slot->comment,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        SessionSlot::insert($data);

        // Regenerar la caché
        (new RedisSlotsService($session))->regenerateCache();
    }
}
