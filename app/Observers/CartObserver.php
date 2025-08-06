<?php

namespace App\Observers;

use App\Models\Cart;
use App\Models\GroupPack;
use Illuminate\Support\Facades\DB;


class CartObserver
{

    public function deleted(Cart $cart)
    {
        //Añadimos la id del usuario que lo elimina
        DB::table('carts')->where('id', $cart->id)->update(['deleted_user_id' => auth()->user()->id]);

        // removing inscriptions from Cart
        $cart->inscriptions()->get()->each(function($inscription)
        {
            //Añadimos la id del usuario que lo elimina
            DB::table('inscriptions')->where('id', $inscription->id)->update(['deleted_user_id' => auth()->user()->id]);
            return $inscription->delete();
        });

        //Eliminamos todos los pagos enlazados al carrito
        $cart->payments()->get()->each(function($payment)
        {
            //Añadimos la id del usuario que lo elimina
            DB::table('payments')->where('id', $payment->id)->update(['deleted_user_id' => auth()->user()->id]);
            return $payment->delete();
        });

       // eliminamos groupPack enlazados al carrito
        $cart->groupPacks->each(function(GroupPack $groupPack)
        {
            //Remove PDF from GroupPack
            $destination_path = brand_setting('base.packs.pdf_folder');
            \Storage::disk()->delete("$destination_path/$groupPack->pdf");

            //Eliminamos las inscripcions de los packs, ya que desde carrito no se enlazan
            foreach($groupPack->inscriptions as $inscription){
                $inscription->delete();
            }

            //Añadimos la id del usuario que lo elimina
            DB::table('group_packs')->where('id', $groupPack->id)->update(['deleted_user_id' => auth()->user()->id]);

            $groupPack->delete();
        });

        
    }

}
