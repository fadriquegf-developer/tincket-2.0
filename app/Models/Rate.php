<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Observers\RateObserver;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class Rate extends BaseModel
{

    use HasTranslations;
    use CrudTrait;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'needs_code',
        'validator_class',
        'form_name',
        'has_rule',
        'rule_parameters',
        'form_id',
        'brand_id',
    ];

    public $appends = ['max_on_sale', 'form'];

    const VALIDATOR_CLASSES = [
        'DiscountCode' => 'DiscountCode'
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(RateObserver::class);
    }

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function getMaxOnSaleAttribute()
    {
        return $this->pivot && $this->pivot->max_on_sale ?
            min([$this->pivot->max_per_order, $this->count_free_positions]) : -1;
    }


    public function getCountFreePositionsAttribute()
    {
        $session = $this->pivot->morphTo('assignated_rate')->getResults() ?? $this->pivot->pivotParent;

        // Returns the number of booked Inscriptions for the given rate
        // prevent calcule if pivot to App\Zone optimization only on Session
        if ($session instanceof Session) {
            return $this->calculeFreePositions();
        }

        return null;
    }

    public function calculeFreePositions()
    {
        // requiret pivot to know session
        if (!$this->pivot) {
            return null;
        }

        $session = Session::find($this->pivot->session_id);
        $rate_id = $this->id;

        // Returns the number of booked Inscriptions for the given rate
        if ($session) {
            $car_ttl = $session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

            $confirmed_inscriptions = Inscription::where('session_id', $session->id)
                ->where('rate_id', $rate_id)
                ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                ->whereNotNull('carts.confirmation_code')
                ->count();

            $not_expired_carts = Inscription::where('session_id', $session->id)
                ->where('rate_id', $rate_id)
                ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                ->where('carts.expires_on', '>', \Carbon\Carbon::now()->subMinutes($car_ttl))
                ->whereNull('carts.confirmation_code')
                ->count();

            $blocked_inscriptions = $confirmed_inscriptions  + $not_expired_carts;

            return min([$session->getFreePositions(), $this->pivot->max_on_sale - $blocked_inscriptions]);
        }

        // TODO throw Exception? What if assignated_rate is not to Session?
        return null;
    }

    public function setValidatorClassAttribute($value)
    {
        // would be better to do it with observer so this makes attribute
        // setting order dependent
        if ((bool) $this->needs_code)
            $this->attributes['validator_class'] = $value;
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function getFormAttribute()
    {

        if ($form = $this->form()->with('form_fields')->get()->first()) {
            return $form;
        }

        return null;
    }

    public function assignatedRates()
    {
        return $this->hasMany(AssignatedRate::class, 'rate_id');
    }

}
