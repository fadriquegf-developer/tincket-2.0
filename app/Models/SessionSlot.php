<?php

namespace App\Models;

use App\Observers\SessionSlotObserver;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class SessionSlot extends BaseModel
{

    use CrudTrait;

    protected $table = 'session_slot';
    protected $fillable = ['session_id', 'slot_id', 'status_id', 'comment'];

    protected static function boot()
    {
        parent::boot();
        SessionSlot::observe(SessionSlotObserver::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function setStatusIdAttribute($value) {
        $this->attributes['status_id'] = $value ?: null;
    }

}
