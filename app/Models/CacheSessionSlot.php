<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheSessionSlot extends BaseModel
{

    protected $table = 'cache_session_slot';
    public $timestamps = false;
    protected $fillable = [
        'session_id', 
        'slot_id', 
        'zone_id',
        'cart_id',
        'is_locked', 
        'comment',
        'rates_info'
    ];

    protected $casts = [
        'rates_info' => 'array'
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

}
