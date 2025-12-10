<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Models\Inscription;
use App\Models\SessionSlot;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\HasTranslations;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use App\Observers\SessionObserver;
use App\Repositories\SessionRepository;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class Session extends BaseModel
{
    const AUTOLOCK_CROSS = 'cross';
    const AUTOLOCK_RIGHT_LEFT = 'right_left';

    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use LogsActivity;
    use CrudTrait;
    use SoftDeletes;
    use HasTranslations;
    use OwnedModelTrait;

    //protected $hidden = ['max_places', 'max_inscr_per_order'];
    protected $fillable = [
        'name', // t
        'slug', // t
        'description', // t
        'min_tickets',
        'max_tickets',
        'starts_on',
        'ends_on',
        'inscription_starts_on',
        'inscription_ends_on',
        'max_places',
        'event_id',
        'space_id',
        'images',
        'is_numbered',
        'tags', //t
        'metadata', //t
        'tpv_id',
        'external_url',
        'autolock_type',
        'autolock_n',
        'limit_x_100',
        'visibility',
        'private',
        'only_pack',
        'session_color',
        'hide_n_positions',
        'banner',
        'custom_logo',
        'session_bg_color',
        'code_type',
        'validate_all_session',
        'brand_id',
        'user_id',
        'limit_per_user',
        'max_per_user',
        'deleted_by',
    ];
    public $translatable = [
        'name',
        'slug',
        'description',
        'tags',
        'metadata',
    ];
    protected $casts = [
        'images' => 'array',
        'validate_all_session' => 'boolean',
        'starts_on' => 'datetime',
        'ends_on' => 'datetime',
        'inscription_starts_on' => 'datetime',
        'inscription_ends_on' => 'datetime',
    ];
    protected $appends = [
        'has_public_rates',
        'redirect_to',
        'name_filter',
        'count_free_positions'
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        Session::observe(SessionObserver::class);

        static::saved(function (self $event) {
            $event->relocateTempUploads();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // TODO check is correct. Location don't have session id
    public function locations()
    {
        return $this->hasMany(Location::class)->withTrashed();
    }

    public function space()
    {
        return $this->belongsTo(Space::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class)
            ->withoutGlobalScope(BrandScope::class);
    }

    /**
     * Accessor que resuelve el TPV heredado cuando session.tpv_id es NULL
     * 
     * @param int|null $value
     * @return int|null
     */
    public function getTpvIdAttribute($value)
    {
        // 1. Si la sesión tiene TPV propio, usarlo
        if ($value !== null) {
            return $value;
        }

        // 2. Si no hay brand_id, retornar null (caso de creación en Backpack)
        if (!isset($this->attributes['brand_id'])) {
            return null;
        }

        // 3. Buscar en extra_config del brand de la sesión (sin BrandScope)
        $brand = Brand::withoutGlobalScope(BrandScope::class)
            ->find($this->attributes['brand_id']);

        return $brand?->extra_config['default_tpv_id'] ?? null;
    }

    /**
     * Relación al TPV (sin BrandScope para permitir TPVs de otros brands)
     */
    public function tpv()
    {
        return $this->belongsTo(Tpv::class, 'tpv_id')
            ->withoutGlobalScope(BrandScope::class);
    }

    public function inscriptions()
    {
        // returns confirmed and not confirmed inscriptions
        return $this->hasMany(Inscription::class);
    }

    public function sessionSlot()
    {
        return $this->hasMany(SessionSlot::class);
    }

    public function sessionTempSlot()
    {
        return $this->hasMany(SessionTempSlot::class);
    }

    public function codes()
    {
        return $this->hasMany(SessionCode::class);
    }

    public function packs()
    {
        return $this->belongsToMany(Pack::class);
    }

    // Convertir accessor en método explícito con cache
    public function getFreePositions(): int
    {
        $service = new RedisSlotsService($this);
        return $service->getFreePositions();
    }

    // En lugar de accessor, método explícito
    public function getAvailableWebPositions(): int
    {
        $service = new RedisSlotsService($this);
        return $service->getAvailableWebPositions();
    }

    public function countBlockedInscriptions(): int
    {
        $service = new RedisSlotsService($this);
        return $service->countBlockedInscriptions();
    }

    public function getSelledInscriptions(bool $onlyWeb = true): int
    {
        return app(SessionRepository::class)->getSelledInscriptions($this, $onlyWeb);
    }

    public function getSelledOfficeInscriptions(): int
    {
        return app(SessionRepository::class)->getSelledOfficeInscriptions($this);
    }

    public function getValidatedCount(): int
    {
        return app(SessionRepository::class)->getValidatedCount($this)['validated'];
    }

    public function getValidatedOutCount(): int
    {
        return app(SessionRepository::class)->getValidatedCount($this)['validated_out'];
    }

    public function getCountFreePositionsAttribute()
    {
        return $this->getAvailableWebPositions();
    }

    /**
     * Todas las tarifas de la sesión (Session, Zone, Slot)
     */
    public function allRates()
    {
        return $this->hasMany(AssignatedRate::class, 'session_id');
    }

    public function getPublicRatesAttribute()
    {
        $cacheKey = "session_{$this->id}_public_rates_formatted";

        return Cache::remember($cacheKey, 60, function () {
            $service = new \App\Services\RedisSlotsService($this);
            $availableWebPositions = $service->getAvailableWebPositions();

            // ✅ DESHABILITAR BrandScope
            return $this->allRates()
                ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->with([
                    'rate' => function ($q) {
                        $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                            ->with([
                                'form' => function ($fq) {
                                    $fq->withoutGlobalScope(\App\Scopes\BrandScope::class)
                                        ->with([
                                            'form_fields' => function ($ffq) {
                                                $ffq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                                            }
                                        ]);
                                }
                            ]);
                    }
                ])
                ->where('is_public', true)
                ->get()
                ->filter(function ($ar) {
                    return $ar->rate !== null;
                })
                ->map(function ($ar) use ($availableWebPositions) {

                    $blockedForRate = $this->calculateBlockedForRate($ar->rate_id);
                    $availableForRate = max(0, $ar->max_on_sale - $blockedForRate);
                    $maxOnSale = min($ar->max_per_order, $availableForRate, $availableWebPositions);

                    return [
                        'id' => $ar->rate->id,
                        'name' => json_decode($ar->rate->getRawOriginal('name'), true),
                        'price' => $ar->price,
                        'needs_code' => $ar->rate->needs_code,
                        'has_rule' => $ar->rate->has_rule,
                        'form_id' => $ar->rate->form_id,
                        'form' => ($form = $ar->rate->relationLoaded('form') ? $ar->rate->getRelation('form') : null) && $form ? [
                            'id' => $form->id,
                            'name' => $form->name,
                            'form_fields' => $form->form_fields->map(function ($field) {
                                return [
                                    'id' => $field->id,
                                    'type' => $field->type,
                                    'name' => $field->name,
                                    'label' => json_decode($field->getRawOriginal('label'), true),
                                    'config' => $field->config,
                                ];
                            })->toArray()
                        ] : null,
                        'rule_parameters' => $ar->rate->rule_parameters,
                        'max_on_sale' => $maxOnSale,
                        'pivot' => [
                            'rate_id' => $ar->rate_id,
                            'price' => $ar->price,
                            'max_on_sale' => $ar->max_on_sale,
                            'max_per_order' => $ar->max_per_order,
                            'is_private' => $ar->is_private ?? false,
                            'max_per_code' => $ar->max_per_code,
                            'available_since' => $ar->available_since,
                            'available_until' => $ar->available_until,
                        ]
                    ];
                });
        });
    }

    private function calculateBlockedForRate($rateId)
    {
        $cartTTL = $this->brand->getSetting(
            Brand::EXTRA_CONFIG['CART_TTL_KEY'],
            Cart::DEFAULT_MINUTES_TO_EXPIRE
        );

        return Inscription::where('session_id', $this->id)
            ->where('rate_id', $rateId)
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->where(function ($q) use ($cartTTL) {
                $q->whereNotNull('carts.confirmation_code')
                    ->orWhere(function ($sq) use ($cartTTL) {
                        $sq->whereNull('carts.confirmation_code')
                            ->where('carts.expires_on', '>', now()->subMinutes($cartTTL));
                    });
            })
            ->count();
    }

    public function currentBrandFrontend()
    {
        $frontendUrl = $this->brand?->getSetting('clients.frontend.url');

        return $frontendUrl
            ? sprintf("%s/redirect/session/%s", trim($frontendUrl, '/'), $this->id)
            : null;
    }

    public function getGeneralRateAttribute()
    {
        $rateData = app(SessionRepository::class)->getGeneralRate($this);

        if (!$rateData) {
            return null;
        }

        $assignatedRate = new AssignatedRate();
        foreach ($rateData as $key => $value) {
            if (property_exists($assignatedRate, $key)) {
                $assignatedRate->{$key} = $value;
            }
        }

        $assignatedRate->price = $rateData->price;

        return $assignatedRate;
    }


    public function getEventNameAttribute()
    {
        // Si la relación ya está cargada, usarla
        if ($this->relationLoaded('event')) {
            return $this->event?->name ?? '-';
        }

        // Si tenemos el event_id pero no la relación, devolver placeholder
        // Esto evita el N+1, el desarrollador debe hacer eager loading
        if ($this->event_id) {
            return '[Event #' . $this->event_id . ']';
        }

        return '-';
    }

    /**
     * Método helper para cargar relaciones necesarias de forma eficiente
     */
    public function loadCommonRelations(): self
    {
        return $this->load([
            'event',
            'space.location',
            'space.zones.slots',
            'brand'
        ]);
    }

    /**
     * Scope para incluir relaciones comunes
     */
    public function scopeWithCommonRelations($query)
    {
        return $query->with([
            'event',
            'space.location',
            'brand'
        ]);
    }

    public function getShowSessionButton()
    {

        $frontendUrl = rtrim(brand_setting('clients.frontend.url'), '/');
        $url = "{$frontendUrl}/redirect/session/{$this->id}";

        return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-link pr-0" data-style="zoom-in">
                <span class="ladda-label">
                    <i class="la la-eye" aria-hidden="true"></i> ' . __('backend.session.show_session') . '
                </span>
            </a>';
    }

    public function getCloneSessionButton()
    {
        $text = app()->getLocale() === 'en' ? 'Clone session' : 'Clonar sesión';

        return '<a href="#" class="btn btn-sm btn-link" onclick="openCloneModal(' . $this->id . ')">
            <i class="la la-clone"></i> ' . $text . '
        </a>';
    }

    public function getHasPublicRatesAttribute($has_public_rates)
    {
        if (!isset($this->attributes['has_public_rates'])) {
            // ✅ DESHABILITAR BrandScope
            $this->attributes['has_public_rates'] = (bool) $this->allRates()
                ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->where('is_public', true)
                ->count();
        }

        return $this->attributes['has_public_rates'];
    }

    public function hasBookedPacks(): bool
    {
        return $this->sessionSlot()->where('status_id', '8')->exists();
    }

    public function getRedirectToAttribute()
    {
        // Si ya se calculó, devolverlo
        if (isset($this->attributes['redirect_to'])) {
            return $this->attributes['redirect_to'];
        }

        // Obtener brand del request
        $currentBrand = request()->get('brand');

        if (!$currentBrand) {
            return null;
        }

        // Si la sesión pertenece a un brand diferente al actual
        if ($this->brand_id != $currentBrand->id) {
            return $this->getRedirectTo();
        }

        return null;
    }

    /**
     * Get url session from the session's own brand frontend
     * 
     * @return string|null
     */
    public function getRedirectTo()
    {
        $sessionBrand = $this->brand;

        if (!$sessionBrand) {
            return null;
        }

        // Cargar capability si no está cargada
        if (!$sessionBrand->relationLoaded('capability')) {
            $sessionBrand->load('capability');
        }

        $capability = $sessionBrand->capability;

        // Verificar el capability del brand DE LA SESIÓN
        if ($capability && $capability->code_name === 'basic') {
            $frontend_url = Setting::withoutGlobalScope(BrandScope::class)
                ->where('brand_id', $sessionBrand->id)
                ->where('key', 'clients.frontend.url')
                ->first();

            if ($frontend_url) {
                return sprintf(
                    "%s/redirect/session/%s",
                    rtrim($frontend_url->value, '/'),
                    $this->id
                );
            }
        }

        return null;
    }

    public function getNameFilterAttribute()
    {
        if (isset($this->event->name)) {
            return 'Sessió ' . $this->starts_on . ' - ' . $this->event->name;
        }

        return 'Sessió ' . $this->starts_on;
    }

    protected function relocateTempUploads(): void
    {
        $singleImageFields = ['custom_logo', 'banner'];

        $brand = get_current_brand()->code_name;
        $baseDir = "uploads/{$brand}/session/{$this->id}";     // destino final
        $disk = Storage::disk('public');

        $dirty = false;
        $tempDirsUsed = [];

        /* ---------- mover cada campo monovalor ---------- */
        foreach ($singleImageFields as $attr) {

            // normaliza separadores «\» → «/»
            $path = str_replace('\\', '/', $this->{$attr});

            // comprueba «/__temp__/» en minúsculas
            if (!$path || !Str::contains(Str::lower($path), '/temp/')) {
                continue; // ya está donde toca o está vacío
            }

            // mueve archivos y obtenemos la carpeta temporal usada
            [$finalPath, $tmpDir] = $this->moveToFinalDir($path, $baseDir, $disk);

            $this->{$attr} = $finalPath;
            $tempDirsUsed[] = str_replace('\\', '/', $tmpDir);  // ⭐
            $dirty = true;
        }

        if ($dirty) {
            $this->saveQuietly();   // evita sessions/updated_at innecesarios
        }
    }

    /**
     * Mueve un archivo (y sus variantes md-/sm-) al directorio final.
     *
     * @return array{0:string,1:string}  [ruta_final, carpeta_temp_origen]
     */
    private function moveToFinalDir(string $original, string $baseDir, $disk): array
    {
        // normaliza separadores para trabajar siempre con «/»
        $original = str_replace('\\', '/', $original);

        if (!$disk->exists($original)) {
            return [$original, dirname($original)];
        }

        $filename = basename($original);
        $dest = "{$baseDir}/{$filename}";

        $disk->makeDirectory($baseDir);
        $disk->move($original, $dest);

        // mover variantes prefijadas (md-, sm-)
        foreach (['md-', 'sm-'] as $pre) {
            $src = dirname($original) . "/{$pre}{$filename}";
            if ($disk->exists($src)) {
                $disk->move($src, "{$baseDir}/{$pre}{$filename}");
            }
        }

        return [$dest, dirname($original)];
    }
}
