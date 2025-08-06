<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @author miquel
 */
class StatsSales extends BaseModel
{

    public $table = 'stats_sales';

    static public function createFromInscription(Inscription $inscription)
    {
        $sale = new static();
        $sale->inscription_id = $inscription->id;
        $sale->client_id = $inscription->cart->client_id;
        $sale->session_id = $inscription->session_id;
        $sale->event_id = $inscription->session->event_id;
        $sale->event_name = $inscription->session->event->name;

        return $sale;
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
    
    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
