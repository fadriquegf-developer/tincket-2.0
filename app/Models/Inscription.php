<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Session;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Observers\InscriptionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Inscription extends BaseModel
{

    use OwnedModelTrait;
    use CrudTrait;
    use SoftDeletes;
    use LogsActivity;

    protected $dates = ['checked_at'];

    protected static function boot()
    {
        parent::boot();
        Inscription::observe(InscriptionObserver::class);
    }

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class)->withTrashed();
    }

    public function group_pack()
    {
        return $this->belongsTo(GroupPack::class);
    }

    public function gift_card()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class)->withTrashed();
    }



    public function slot()
    {
        return $this->belongsTo(Slot::class, 'slot_id', 'id')
            ->select('slots.*')
            ->selectRaw('? as pivot_session_id', [$this->session_id]);
    }

    public function scopeForBrand(Builder $q, Brand $brand): Builder
    {
        return $q->with(['cart.confirmedPayment'])
            ->when(
                get_brand_capability() == 'basic',
                fn($q) => $q->whereHas(
                    'cart',
                    fn($c) =>
                    $c->where('brand_id', $brand->id)
                ),
                fn($q) => $q->whereHas(
                    'session',
                    fn($s) =>
                    $s->where('brand_id', $brand->id)
                )
            )
            ->whereHas(
                'cart.confirmedPayment',
                fn($p) =>
                $p->whereNotNull('paid_at')
            )
            ->select('inscriptions.*');
    }


    public function scopePaid($query)
    {
        return $query->whereHas('cart.confirmedPayment');
    }

    public function isGift()
    {
        return $this->gift_card_id !== NULL;
    }

    public function getRateName()
    {
        if ($this->isGift()) {
            return __('backend.gift_card.gift_card');
        }

        $rate = $this->rate;
        if ($rate) {
            return $rate->name;
        }

        return '';
    }

    public function getBanner()
    {
        //Pack > Session > Event
        if (isset($this->group_pack) && $this->group_pack->pack->banner) {
            return $this->group_pack->pack->banner;
        } elseif ($this->session->banner != NULL) {
            return $this->session->banner;
        } elseif ($this->session->event->banner != NULL) {
            return $this->session->event->banner;
        } else {
            return $this->cart->brand->banner;
        }
    }

    public function getPdfNameAttribute($value)
    {
        if (!$value) {
            // filename has pattern: "BRANDID"-"YYYYMMDD OF SESSION"-"CONFIRM ORDER CODE"-"SUBSTR(INSCRIPTION BARCODE, 8)".pdf
            $value = sprintf("%s-%s-%s-%s.pdf", $this->cart->brand->id, $this->session->starts_on->format('Ymd'), $this->cart->confirmation_code, strtoupper(substr($this->barcode, 0, 8)));
        }

        return $value;
    }

    public function getLogo()
    {
        $cartBrand = $this->cart->brand;
        $defaultLogo = $cartBrand->logo;

        // check if event has custom logo
        $customLogo = $this->session->event->custom_logo ? $this->session->event->custom_logo : null;

        // priority logo who sell event(cart owner)
        if (!$customLogo || $cartBrand->id !== $this->session->event->brand->id) {
            return $defaultLogo;
        }

        return brand_asset(\Storage::url('uploads/' . $customLogo), $this->cart->brand);
    }

}
