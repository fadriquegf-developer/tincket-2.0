<?php

namespace App\Jobs;

use App\Models\Session;
use App\Models\SessionSlot;
use Illuminate\Bus\Queueable;
use App\Models\SessionTempSlot;
use App\Models\CacheSessionSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RegenerateSession implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $session;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Handle the event.
     * 
     * @return void
     */
    public function handle()
    {
        $this->regenerateCache();
    }

      /**
     * Regenerate entire slot state cache for the current session
     */
    public function regenerateCache()
    {
        if (!$this->session->is_numbered)
            return false;

        $slots = $this->session->configuration->slots;

        DB::transaction(function() use ($slots)
        {
            CacheSessionSlot::where('session_id', $this->session->id)->delete();

            //Creamos los slots en la cache
            $slots->each(function ($slot) {
                CacheSessionSlot::create($slot->arrayCacheSlot());
            });  
            
            // Set status from SessionSlot table set by admin in session and Set is_locked 0 where lock_reason is Reduced Mobility (6) 
            SessionSlot::where('session_id', $this->session->id)->whereNotNull('status_id')->get()->each(function($slot)
            {
                \DB::update("UPDATE cache_session_slot SET cart_id = NULL, is_locked = ?, lock_reason = ?, comment = ? WHERE session_id = ? and slot_id = ?;", [
                    $slot->status_id == 6 ? 0 : 1, $slot->status_id, $slot->comment, $slot->session_id, $slot->slot_id
                ]);
            });
    
           // Set status from SessionTempSlot table set by autolock
           SessionTempSlot::notExpired()->where('session_id', $this->session->id)->whereNotNull('status_id')->get()->each(function($slot)
           {
               DB::update("UPDATE cache_session_slot SET cart_id = ?, is_locked = 1, lock_reason = ? WHERE session_id = ? and slot_id = ?;", [
                   $slot->cart_id, $slot->status_id, $slot->session_id, $slot->slot_id
               ]);
           });
        });

    }

}
