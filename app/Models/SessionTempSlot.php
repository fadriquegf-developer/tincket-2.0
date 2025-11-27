<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\SessionTempSlotObserver;
use App\Scopes\BrandScope;
use Backpack\CRUD\app\Models\Traits\CrudTrait;


/**
 * Similar to SessionSlot allow to change status from slot but only by set amount of time
 * If expires_on is null SessionTempSlot does not expire
 */
class SessionTempSlot extends BaseModel
{

    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'session_temp_slot';
    protected $fillable = ['session_id', 'slot_id', 'inscription_id', 'status_id', 'cart_id', 'expires_on'];
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expires_on',
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(SessionTempSlotObserver::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_on', '>', \Carbon\Carbon::now())
                ->orWhereNull('expires_on');
        });
    }

    public function setStatusIdAttribute($value)
    {
        $this->attributes['status_id'] = $value ?: null;
    }
}
