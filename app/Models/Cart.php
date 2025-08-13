<?php

namespace App\Models;

use App\Models\Brand;
use App\Models\Client;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Observers\CartObserver;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Cart extends BaseModel
{

    const DEFAULT_MINUTES_TO_EXPIRE = 5;
    const DEFAULT_MINUTES_TO_COMPLETE = 60; //still not used. Time max for a Cart to be bought

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
        'confirmation_code',
    ];

    protected $appends = [
        'price_sold'
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
     * 
     * Devuelvo todas las inscripciones que tenga el carrito, que no sean pack
     * 
     */

    public function inscriptions()
    {
        return $this->HasMany(Inscription::class)->where('group_pack_id', NULL);
    }

    public function allInscriptions()
    {
        return $this->HasMany(Inscription::class);
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

    public function groupPacks()
    {
        return $this->hasMany(GroupPack::class);
    }

    public function gift_cards()
    {
        return $this->hasMany(GiftCard::class);
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
        /* Fadri: sabem perque consideram expirat un carrito, a partir del created_at? Aixo no dona error si el expires on es mayor? */
        /* return $this->expires_on < \Carbon\Carbon::now() ||
            $this->created_at->addMinutes($this->brand->config(Brand::EXTRA_CONFIG['CART_MAX_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_COMPLETE)) < \Carbon\Carbon::now(); */
        return $this->expires_on < \Carbon\Carbon::now();
    }

    public function getPriceSoldAttribute(): float
    {
        // Usar load en lugar de loadMissing para forzar recarga
        $this->load([
            'groupPacks.pack',
            'groupPacks.inscriptions',
            'inscriptions',
            'gift_cards',
        ]);

        // Separar GroupPacks por tipo de redondeo
        [$rounded, $notRounded] = $this->groupPacks->partition(
            fn($gp) => $gp->pack->cart_rounded
        );

        // Para packs con redondeo, redondear la suma total
        $priceRounded = round(
            $rounded->flatMap->inscriptions->sum('price_sold'),
            0
        );

        // Para packs sin redondeo, mantener 2 decimales
        $priceNotRounded = round(
            $notRounded->flatMap->inscriptions->sum('price_sold'),
            2
        );

        // Inscripciones directas (sin pack)
        $priceInscriptions = round(
            $this->inscriptions
                ->whereNull('group_pack_id')
                ->reject(fn($insc) => $insc->gift_card_id !== null)
                ->sum('price_sold'),
            2
        );

        // Gift cards
        $priceGiftCards = $this->gift_cards->sum('price');

        return $priceRounded + $priceNotRounded + $priceInscriptions + $priceGiftCards;
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
        // if Cart is in DB, we check before if it is already expired
        if ($this->exists && $this->getIsExpiredAttribute())
            return false;

        $this->expires_on = \Carbon\Carbon::now()->addMinutes($this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE));

        // extend time sessionTempSlots
        SessionTempSlot::where('cart_id', $this->id)->update(['expires_on' => $this->expires_on]);

        return $this->save();
    }

    public function expiredTime()
    {

        $this->expires_on = \Carbon\Carbon::now()->subMinutes(1);

        // extend time sessionTempSlots
        SessionTempSlot::where('cart_id', $this->id)->update(['expires_on' => $this->expires_on]);

        return $this->save();
    }
}
