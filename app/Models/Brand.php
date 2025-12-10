<?php

namespace App\Models;

use App\Models\Page;
use App\Models\User;
use App\Models\Client;
use App\Models\Setting;
use App\Models\Capability;
use App\Observers\BrandObserver;
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

    protected static function boot()
    {
        parent::boot();

        // Registrar el observer
        static::observe(BrandObserver::class);
    }

    /**
     * Obtiene las marcas que tienen a esta marca como hija
     * (es decir, las marcas "padre" que pueden vender mis eventos)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPartnershipedBrandsAttribute()
    {
        // Si esta marca tiene un padre, devuelve una colección con ese padre
        // Si no tiene padre, devuelve una colección vacía
        if ($this->parent_id) {
            return collect([$this->parent]);
        }

        return collect();
    }


    /**
     * Obtiene las marcas hijas de esta marca
     * (es decir, las marcas de las cuales puedo vender eventos)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPartnershipedChildBrandsAttribute()
    {
        return $this->children;
    }

    public function hasRelations(): bool
    {
        return $this->applications()->exists() ||
            $this->users()->exists() ||
            $this->events()->exists() ||
            $this->tpvs()->exists() ||
            $this->clients()->exists() ||
            $this->pages()->exists() ||
            $this->children()->exists();
    }

    /**
     * URLs frontend from current brand not event brand and for all types promotor and basic
     * Similar to getRedirectTo but specifically for profile page
     * 
     * @return string
     */
    public function frontendProfile()
    {
        // Obtener URL del frontend desde brand_setting o config
        $frontend_url = brand_setting('clients.frontend.url', $this) ?? config('clients.frontend.url');

        return sprintf("%s/%s", trim($frontend_url, '/'), "perfil");
    }

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
    public function getLogoUrlAttribute(): string
    {
        if (!$this->logo) {
            return '';
        }

        // Si ya es URL completa
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Convertir a URL absoluta
        $path = str_replace('\\', '/', $this->logo);

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        if (str_starts_with($path, 'uploads/')) {
            return url('storage/' . $path);
        }

        return url('storage/' . ltrim($path, '/'));
    }

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
        return $this->users()
            ->whereIn('users.id', config('superusers.ids', [1]))
            ->pluck('users.id')
            ->toArray();
    }
}
