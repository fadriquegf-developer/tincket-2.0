<?php

namespace App\Models;

use App\Models\Brand;
use App\Models\Client;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Observers\CartObserver;
use App\Services\InscriptionService;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Cart extends BaseModel
{

    const DEFAULT_MINUTES_TO_EXPIRE = 5;
    const DEFAULT_MINUTES_TO_COMPLETE = 60;

    use CrudTrait;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expires_on',
    ];

    protected $fillable = [
        'expires_on',
        'brand_id',
        'client_id',
        'confirmation_code',
        'seller_id',
        'seller_type',
    ];

    protected $appends = [
        'price_sold'
    ];

    protected $casts = [
        'expires_on' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }


    protected static function boot()
    {
        parent::boot();
        Cart::observe(CartObserver::class);
    }

    /**
     * Inscripciones que NO son de packs
     * ✅ DESHABILITAR BrandScope para permitir inscripciones de brands hijos
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class)
            ->withoutGlobalScope(BrandScope::class)
            ->where('group_pack_id', NULL);
    }

    /**
     * TODAS las inscripciones (incluidas las de packs)
     * ✅ DESHABILITAR BrandScope para permitir inscripciones de brands hijos
     */
    public function allInscriptions()
    {
        return $this->hasMany(Inscription::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * ✅ DESHABILITAR BrandScope para permitir packs de brands hijos
     */
    public function groupPacks()
    {
        return $this->hasMany(GroupPack::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    /**
     * ✅ DESHABILITAR BrandScope para permitir gift cards de brands hijos
     */
    public function gift_cards()
    {
        return $this->hasMany(GiftCard::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    public function deletedUser()
    {
        return $this->belongsTo(User::class, 'deleted_user_id');
    }

    public function seller()
    {
        return $this->morphTo('seller');
    }

    public function isPayable(): bool
    {
        return is_null($this->confirmation_code)
            && is_null($this->deleted_at)
            && (
                is_null($this->expires_on)
                || \Carbon\Carbon::parse($this->expires_on)->isFuture()
            );
    }

    /**
     * Returns the successful payment of the given cart
     */
    public function confirmedPayment()
    {
        return $this->hasOne(Payment::class, 'cart_id', 'id')
            ->whereNotNull('paid_at');
    }

    public function getPaymentAttribute()
    {
        return $this->payments()
            ->whereNotNull('paid_at')
            ->where('order_code', $this->confirmation_code)
            ->first();
    }


    /**
     * A Cart is expired when expires_on date is already passed or created_at
     * is passed more than max TTL attribute (60 minutes, for example)
     * 
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_on < \Carbon\Carbon::now();
    }

    public function getPriceSoldAttribute(): float
    {
        return app(InscriptionService::class)->calculateCartPrice($this);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmation_code');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_on', '>', \Carbon\Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_on', '<', \Carbon\Carbon::now());
    }

    public function scopeWithInscriptions($query)
    {
        return $query->has('allInscriptions', '>', 0);
    }

    public function confirmTempSlot()
    {
        SessionTempSlot::where('cart_id', $this->id)->update(['expires_on' => null]);
    }

    public function getIsConfirmedAttribute()
    {
        return $this->confirmation_code !== null;
    }

    public function extendTime()
    {
        if ($this->exists && $this->getIsExpiredAttribute())
            return false;

        $newExpiresOn = \Carbon\Carbon::now()->addMinutes(
            $this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE)
        );

        $this->expires_on = $newExpiresOn;

        // extend time sessionTempSlots
        SessionTempSlot::where('cart_id', $this->id)->update(['expires_on' => $newExpiresOn]);

        if ($this->save()) {
            $this->refresh(); // Asegurar que se recarga con los casts correctos
            return true;
        }

        return false;
    }

    public function expiredTime()
    {

        $this->expires_on = \Carbon\Carbon::now()->subMinutes(1);

        // extend time sessionTempSlots
        SessionTempSlot::where('cart_id', $this->id)->update(['expires_on' => $this->expires_on]);

        return $this->save();
    }
}
