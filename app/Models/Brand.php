<?php

namespace App\Models;

use App\Models\Page;
use App\Models\User;
use App\Models\Client;
use App\Models\Setting;
use App\Models\Capability;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends BaseModel
{
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use HasTranslations;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    const EXTRA_CONFIG = [
        "CART_TTL_KEY" => 'cartTTL',
        "CART_MAX_TTL_KEY" => 'maxCartTTL',
    ];

    protected $hidden = ['key', 'extra_config', 'allowed_host', 'custom_script', 'aux_code', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'name',
        'code_name',
        'key',
        'type',
        'capability_id',
        'allowed_host',
        'brand_color',
        'logo',
        'banner',
        'alert',
        'alert_status',
        'custom_script',
        'aux_code',
        'extra_config',
        'phone',
        'email',
        'comment',
        'register',
        'parent_id'
    ];

    protected $fakeColumns = ['extra_config'];

    protected $casts = [
        'extra_config' => 'array',
        'register' => 'array'
    ];

    public $translatable = [
        'footer',
        'privacy_policy',
        'description',
        'alert',
        'legal_notice',
        'cookies_policy',
        'general_conditions',
        'gdpr_text',
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /* public function getLogoAttribute($value)
    {
        if (!$value) {
            return config('base.default_img', 'https://engine.yesweticket.com/images/placeholder.png');
        }

        return brand_asset(\Storage::url($value), $this);
    }

    public function getBannerAttribute($value)
    {
        if (!$value) {
            return false;
        }

        return brand_asset(\Storage::url($value), $this);
    } */


    public function getPartnershipedBrandsAttribute()
    {
        return static::get()  // todas las marcas
            ->filter(function (Brand $brand) {
                // desactivamos el scope sólo para esta query de Settings
                $setting = Setting::withoutGlobalScope(BrandScope::class)
                    ->where('brand_id', $brand->id)
                    ->where('key', 'base.brand.partnershiped_ids')
                    ->first();

                if (!$setting || !trim($setting->value)) {
                    return false;
                }

                $ids = array_map('intval', explode(',', $setting->value));

                return in_array($this->id, $ids);
            });
    }


    public function getPartnershipedChildBrandsAttribute()
    {
        // Obtiene solo el valor del setting; si no existe devuelve null.
        $idsString = Setting::where('brand_id', $this->id)
            ->where('key', 'base.brand.partnershiped_ids')
            ->value('value');                //   null | "1,2,3"

        // Sin configuración ⇒ colección vacía.
        if (blank($idsString)) {
            return collect();
        }

        // Normaliza: separa, quita espacios, convierte a int y filtra vacíos.
        $ids = collect(explode(',', $idsString))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->all();                         // [1, 2, 3]

        return static::whereIn('id', $ids)->get();
    }

    /* public function getLogoPathAttribute()
    {
        $value = $this->attributes['logo'];
        if (!$value) {
            return config('base.default_img', '/images/placeholder.png');
        }

        return $value;
    } */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function capability()
    {
        return $this->belongsTo(Capability::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function parent()
    {
        return $this->belongsTo(Brand::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Brand::class, 'parent_id');
    }

    public function tpvs()
    {
        return $this->hasMany(Tpv::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }



    public function register_inputs()
    {
        return $this->belongsToMany(RegisterInput::class, 'brands_register_inputs', 'brand_id', 'register_input_id')->withPivot('required')->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setCodeNameAttribute($value)
    {
        $this->attributes['code_name'] = $value;
        $tld = app()->environment('production') ? '.com' : '.test';
        $this->attributes['allowed_host'] = $value . '.yesweticket' . $tld;
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings->firstWhere('key', $key)?->value ?? $default;
    }

    public function getBrandSuperAdmins()
    {
        $admi_ids = Setting::where('brand_id', $this->id)->where('key', 'base.brand.admins_ids')->first()->value ?? '';

        // check if user are allowed to login to current brand
        return $this->users()->whereIn('id', explode(',', $admi_ids))->get()->pluck('id');
    }



}
