<?php

namespace App\Observers;

use App\Models\Tpv;

class TpvObserver
{
    public function saving(Tpv $tpv)
    {
        // Si se marca como default, desmarcar otros de la misma brand
        if ($tpv->is_default && $tpv->isDirty('is_default')) {
            Tpv::where('brand_id', $tpv->brand_id)
                ->where('id', '!=', $tpv->id)
                ->update(['is_default' => false]);
        }
    }

    public function deleting(Tpv $tpv)
    {
        // Si se elimina el default, asignar otro
        if ($tpv->is_default) {
            $newDefault = Tpv::where('brand_id', $tpv->brand_id)
                ->where('id', '!=', $tpv->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->first();

            if ($newDefault) {
                $newDefault->is_default = true;
                $newDefault->saveQuietly();
            }
        }
    }
}
