<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This class is the third model need to make ManyToMany relation between
 * Cart and Pack.
 * 
 * We need it as Model because Inscription can be related with it
 * 
 * @author Miquel Serralta <miquel@javajan.com>
 */
class GroupPack extends BaseModel
{

    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = "group_packs";
    protected $fillable = ['pack_id', 'cart_id'];
    public $timestamps = false;
    public $translatable = false;

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    public function inscriptions()
    {
        return $this->HasMany(Inscription::class);
    }

    public function getPdfNameAttribute($value)
    {
        if (!$value) {
            // filename has pattern: "BRANDID"-"CONFIRM ORDER CODE"-"ID PACK"-"ID GROUPPACK.pdf
            $value = sprintf(
                "%s-%s-%s-%s.pdf",
                $this->cart->brand->id,
                $this->cart->confirmation_code,
                $this->pack->id,
                $this->id
            );
        }

        return $value;
    }

    public function getPriceAttribute()
    {
        $round = ($this->pack->cart_rounded ? 0 : 2) ?? 2;
        return round($this->inscriptions->sum('price_sold'), $round);
    }
}
