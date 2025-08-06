<?php

namespace App\Observers;

use App\Models\Inscription;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;


class InscriptionObserver
{

    /**
     * It is executed everytime an Inscription has been deleted
     * 
     * @param Inscription $inscription
     */
    public function deleted(Inscription $inscription)
    {
        //Añadimos la id del usuario que lo elimina
        $user = auth()->user();
        if ($user) {
            \DB::table('inscriptions')->where('id', $inscription->id)->update(['deleted_user_id' => $user->id]);
        }

        \DB::table('stats_sales')->where('inscription_id', $inscription->id)->delete();

        if ($inscription->slot)
            (new \App\Services\Api\SlotCacheService($inscription->session()->first()))->freeSlot($inscription->slot()->first());


        // remove temp inscriptions
        SessionTempSlot::where('inscription_id', $inscription->id)->get()->each(function($sessionTempSlot)
        {
            return $sessionTempSlot->delete();
        });

        // if is a sell delete sessionslot
        if($inscription->barcode){
            SessionSlot::where('session_id', $inscription->session_id)->where('slot_id', $inscription->slot_id)->where('status_id', 2)->get()->each(function($sessionSlot)
            {
                return $sessionSlot->delete();
            });
        }

        //Remove PDF
        $destination_path = brand_setting('base.inscription.pdf_folder');
        \Storage::disk()->delete("$destination_path/$inscription->pdf");

    }

    public function saved(Inscription $inscription)
    {
        if ($old_slot_id = $inscription->getOriginal('slot_id'))
            (new \App\Services\Api\SlotCacheService($inscription->session()->first()))->freeSlot(\App\Models\Slot::find($old_slot_id)->first());

        if ($inscription->slot)
            (new \App\Services\Api\SlotCacheService($inscription->session()->first()))->lockSlot($inscription->slot()->first());

        if ((!$inscription->getOriginal('pdf') && $inscription->pdf) || ($inscription->getOriginal('deleted_at') && !$inscription->deleted_at))
            \App\Models\StatsSales::createFromInscription($inscription)->save();
    }

}
