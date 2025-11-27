<?php

namespace App\Models;

use App\Models\Brand;
use App\Observers\GiftCardObserver;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\HasTranslations;
use App\Traits\OwnedModelTrait;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class GiftCard extends BaseModel
{
    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use HasTranslations;
    use OwnedModelTrait;
    use LogsActivity;

    protected $hidden = [];
    protected $fillable = [
        'brand_id',
        'event_id',
        'cart_id',
        'code',
        'email',
        'price',
        'pdf'
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        GiftCard::observe(GiftCardObserver::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function inscription()
    {
        return $this->hasOne(Inscription::class);
    }

    public function generateCode()
    {
        $this->code = str_random(10);
        $this->save();
    }

    public function getPdfNameAttribute($value)
    {
        if ($value) {
            return basename($value); // siempre solo el nombre del fichero
        }

        return sprintf("%s-%s-%s.pdf", $this->cart->brand->id, $this->cart->confirmation_code, $this->id);
    }

    public function scopeHasEmail($query)
    {
        return $query->whereNotNull('email');
    }

    public function scopeNotHasEmail($query)
    {
        return $query->whereNull('email');
    }

    public function getLogo()
    {
        $cartBrand = $this->cart->brand;
        $defaultLogo = $cartBrand->logo;

        return $defaultLogo;
    }
}
