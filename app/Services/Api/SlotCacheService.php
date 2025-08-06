<?php

namespace App\Services\Api;

use App\Models\Slot;
use App\Models\Zone;
use App\Models\Session;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;
use App\Models\CacheSessionSlot;
use Illuminate\Support\Facades\DB;

class SlotCacheService
{
    private Session $session;
    private bool $show_private_rates = false;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function showPrivateRates(bool $show_private): self
    {
        $this->show_private_rates = $show_private;
        return $this;
    }

    public function getSlotsState(): void
    {
        if (!$this->session->is_numbered) return;

        if (!$this->isCached()) {
            $this->regenerateCache();
        }

        $zones = $this->session->space->zones ?? [];
        foreach ($zones as $zone) {
            $this->getSlotsZoneState($zone);
        }
    }

    public function regenerateCache(): bool
    {
        if (!$this->session->is_numbered) return false;

        $slots = $this->session->space->slots()->with('zone')->get();

        DB::transaction(function () use ($slots) {
            $cachedSlots = CacheSessionSlot::where('session_id', $this->session->id)
                ->get()
                ->keyBy('slot_id');

            $slotsToInsert = [];
            $slotsToUpdate = [];

            foreach ($slots as $slot) {
                $slot->pivot_session_id = $this->session->id;
                $slot->pivot_zone_id = $slot->zone_id;

                $arrayCacheSlot = $slot->arrayCacheSlot();

                if (isset($cachedSlots[$slot->id])) {
                    if ($cachedSlots[$slot->id]->toArray() !== $arrayCacheSlot) {
                        $slotsToUpdate[] = $arrayCacheSlot;
                    }
                } else {
                    $slotsToInsert[] = $arrayCacheSlot;
                }
            }

            if (!empty($slotsToInsert)) {
                CacheSessionSlot::insert($slotsToInsert);
            }

            foreach ($slotsToUpdate as $slot) {
                CacheSessionSlot::where('session_id', $slot['session_id'])
                    ->where('slot_id', $slot['slot_id'])
                    ->update($slot);
            }

            SessionSlot::where('session_id', $this->session->id)
                ->whereNotNull('status_id')
                ->get()
                ->each(function ($slot) {
                    DB::update("UPDATE cache_session_slot SET cart_id = NULL, is_locked = ?, lock_reason = ?, comment = ? WHERE session_id = ? AND slot_id = ?", [
                        $slot->status_id == 6 ? 0 : 1,
                        $slot->status_id,
                        $slot->comment,
                        $slot->session_id,
                        $slot->slot_id,
                    ]);
                });

            SessionTempSlot::notExpired()
                ->where('session_id', $this->session->id)
                ->whereNotNull('status_id')
                ->get()
                ->each(function ($slot) {
                    DB::update("UPDATE cache_session_slot SET cart_id = ?, is_locked = 1, lock_reason = ? WHERE session_id = ? AND slot_id = ?", [
                        $slot->cart_id,
                        $slot->status_id,
                        $slot->session_id,
                        $slot->slot_id,
                    ]);
                });
        });

        return true;
    }

    public function isCached(): bool
    {
        return DB::table('cache_session_slot')
            ->where('session_id', $this->session->id)
            ->exists();
    }

    public function freeExpiredSlotsSession(): void
    {
        DB::update("UPDATE cache_session_slot
            INNER JOIN carts ON carts.id = cache_session_slot.cart_id
            SET cache_session_slot.cart_id = NULL, cache_session_slot.is_locked = 0, cache_session_slot.lock_reason = NULL
            WHERE carts.confirmation_code IS NULL
            AND carts.expires_on < ?
            AND carts.created_at > ?", [
            now(), now()->subDays(1),
        ]);

        DB::update("UPDATE cache_session_slot
            INNER JOIN session_slots ON session_slots.slot_id = cache_session_slot.slot_id
            SET cache_session_slot.lock_reason = session_slots.status_id
            WHERE cache_session_slot.session_id = ?
            AND session_slots.status_id IN (6, 8)
            AND cache_session_slot.cart_id IS NULL", [
            $this->session->id,
        ]);

        DB::update("UPDATE cache_session_slot
            INNER JOIN session_temp_slots ON session_temp_slots.slot_id = cache_session_slot.slot_id
            SET cache_session_slot.cart_id = session_temp_slots.cart_id,
                cache_session_slot.is_locked = 1,
                cache_session_slot.lock_reason = session_temp_slots.status_id
            WHERE session_temp_slots.session_id = ?
            AND session_temp_slots.expires_on > NOW()", [
            $this->session->id,
        ]);
    }

    public function lockSlot(Slot $slot, $reason = null, $comment = null, $cart_id = null): void
    {
        $slot->pivot_session_id = $this->session->id;
        $cart_id = $slot->inscription?->cart->id ?? $cart_id;

        if (is_null($cart_id)) {
            DB::update("UPDATE cache_session_slot SET cart_id = NULL, is_locked = 1, lock_reason = ?, comment = ? WHERE session_id = ? and slot_id = ?", [
                $reason, $comment, $this->session->id, $slot->id,
            ]);
        } else {
            DB::update("UPDATE cache_session_slot SET cart_id = ?, is_locked = 1, lock_reason = ? WHERE session_id = ? and slot_id = ?", [
                $cart_id, $reason, $this->session->id, $slot->id,
            ]);
        }
    }

    public function freeSlot(?Slot $slot = null, $comment = null): void
    {
        if (!$slot) return;

        $locked = 0;
        $lock_reason = null;

        $session_slot = SessionSlot::whereSessionId($this->session->id)->whereSlotId($slot->id)->first();

        if ($session_slot) {
            switch ($session_slot->status_id) {
                case 3:
                    $locked = 1;
                    $lock_reason = 3;
                    break;
                case 6:
                    $lock_reason = 6;
                    break;
                case 8:
                    $locked = 1;
                    $lock_reason = 8;
                    break;
            }
        }

        DB::update("UPDATE cache_session_slot
            SET cart_id = NULL, is_locked = ?, lock_reason = ?, comment = ?
            WHERE session_id = ? AND slot_id = ?", [
            $locked, $lock_reason, $comment, $this->session->id, $slot->id,
        ]);
    }

    private function getSlotsZoneState(Zone &$zone): void
    {
        $zone->slots = Slot::join('cache_session_slot', 'cache_session_slot.slot_id', '=', 'slots.id')
            ->select([
                'cache_session_slot.slot_id as id',
                'cache_session_slot.zone_id',
                'cache_session_slot.lock_reason',
                'cache_session_slot.session_id as pivot_session_id',
                'cache_session_slot.rates_info',
                'cache_session_slot.is_locked',
                'cache_session_slot.comment',
                'slots.name',
                'slots.x',
                'slots.y'
            ])
            ->where('cache_session_slot.session_id', $this->session->id)
            ->where('cache_session_slot.zone_id', $zone->id)
            ->get();

        $zone->slots->each(function ($slot) {
            $show_privates = $this->show_private_rates;

            $rates = collect(json_decode($slot->rates_info ?? '[]'))
                ->filter(fn($rate) => $show_privates || ($rate->pivot->is_public ?? false));

            $slot->setAttribute('rates', $rates);
        });
    }
}
