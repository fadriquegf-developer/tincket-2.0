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
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

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
        'deleted_by',
    ];
    public $translatable = [
        'name',
        'slug',
        'description',
        'tags',
        'metadata',
    ];
    /* protected $dates = [
        'starts_on',
        'ends_on',
        'inscription_starts_on',
        'inscription_ends_on',
    ]; */
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
        'has_booked_packs',
        'redirect_to',
        'count_free_positions',
        'count_available_web_positions',
        'name_filter'
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
        return $this->belongsTo(Space::class)->withTrashed();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tpv()
    {
        return $this->belongsTo(Tpv::class);
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

    /**
     * Returns the relation of all (public and private) rates associated
     * to this Session
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function rates()
    {
        return $this->morphToMany(
            Rate::class,
            'assignated_rate',
            'assignated_rates',
            'assignated_rate_id',
            'rate_id'
        )
            ->wherePivot('assignated_rate_type', self::class)
            ->wherePivot('session_id', $this->id)
            ->withPivot([
                'id',
                'price',
                'session_id',
                'max_on_sale',
                'max_per_order',
                'assignated_rate_type',
                'available_since',
                'available_until',
                'is_public',
                'is_private',
                'max_per_code',
                'validator_class',
            ]);
    }

    /**
     * Unlike rates() which returns all the rates that is morphed to session,
     * this method returns all the rates of the session, wherever it comes from 
     * Session, Zone or Slot
     */
    public function all_rates()
    {
        return $this->hasMany(AssignatedRate::class);
    }

    /**
     * Mix of rates and all_rates
     * Get all the rates of the session like all_rates but 
     * insted of return AssignatedRate model return Rates model withPivot
     */
    public function all_rates_rates()
    {
        return $this->belongsToMany(
            Rate::class,
            'assignated_rates',
            'session_id',
            'rate_id'
        )->withPivot(['id', 'price', 'session_id', 'max_on_sale', 'max_per_order', 'assignated_rate_type', 'available_since', 'available_until', 'is_public', 'is_private', 'max_per_code', 'validator_class'])->where('is_public', 1);
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
        $today = now();

        // Buscar la tarifa privada válida con el mayor precio
        $privateRate = $this->all_rates()
            ->where('is_private', true)
            ->where(function ($query) use ($today) {
                $query->where(function ($subQuery) use ($today) {
                    $subQuery->whereNotNull('available_since')
                        ->where('available_since', '<=', $today);
                })
                    ->orWhereNull('available_since');
            })
            ->where(function ($query) use ($today) {
                $query->where(function ($subQuery) use ($today) {
                    $subQuery->whereNotNull('available_until')
                        ->where('available_until', '>=', $today);
                })
                    ->orWhereNull('available_until');
            })
            ->orderBy('price', 'DESC')
            ->first();

        // Si no encontramos ninguna tarifa privada válida, devolvemos la tarifa con mayor precio
        if (!$privateRate) {
            return $this->all_rates()
                ->orderBy('price', 'DESC')
                ->first();
        }

        return $privateRate;
    }

    public function getSelledInscriptions($onlyWeb = true)
    {
        return Inscription::paid()
            ->where('session_id', $this->id)
            ->whereHas('cart', function ($q) use ($onlyWeb) {
                if ($onlyWeb) {
                    // Ventas web: Sermepa, SermepaSoapService o Free
                    $q->whereHas('payments', function ($p) {
                        $p->whereIn('gateway', ['Sermepa', 'SermepaSoapService', 'Free']);
                    });
                } else {
                    // Otras ventas
                    $q->whereHas('payments', function ($p) {
                        $p->whereNotIn('gateway', ['Sermepa', 'SermepaSoapService', 'Free']);
                    });
                }
            })
            ->count();
    }

    public function getSelledOfficeInscriptions()
    {
        return Inscription::paid()
            ->where('session_id', $this->id)
            ->whereHas('cart.payments', function ($q) {
                // Ventas de taquilla: TicketOffice
                $q->where('gateway', 'TicketOffice');
            })
            ->count();
    }

    public function getEventNameAttribute()
    {
        $evt = $this->relationLoaded('event')
            ? $this->event
            : $this->event()->withTrashed()->first();

        return $evt?->name ?? '-';
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
        return '<a href="#" class="btn btn-sm btn-link" onclick="openCloneModal(' . $this->id . ')">
                <i class="la la-clone"></i> Clonar sesión
            </a>';
    }

    public function getTpvNameForInscriptions()
    {
        $ywtNIF = null;
        $ywtName = null;

        $tpv_id = $this->attributes['tpv_id'];
        if ($tpv_id) {
            $tpv = Tpv::find($tpv_id);
            $tpv_config = new \App\Services\TpvConfigurationDecoder($tpv->config);
            $ywtNIF = $tpv_config->ywtNIF;
            $ywtName = $tpv_config->ywtName;
        }

        return (object) ['ywtNIF' => $ywtNIF, 'ywtName' => $ywtName];
    }

    public function countBlockedInscriptions()
    {
        $confirmed_inscriptions = $this->inscriptions()
            ->paid()
            ->count();

        $not_expired_carts = $this->inscriptions()
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->whereNull('carts.confirmation_code')
            ->where('carts.expires_on', '>', \Carbon\Carbon::now()->subMinutes($this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE)))
            ->count();

        $sessionTempSlot = $this->inscriptions()
            ->join('session_temp_slot', 'session_temp_slot.inscription_id', '=', 'inscriptions.id')
            ->where('session_temp_slot.expires_on', '>', \Carbon\Carbon::now()->subMinutes($this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE)))
            ->count();

        return $confirmed_inscriptions + $not_expired_carts + $sessionTempSlot;
    }

    public function getCountFreePositionsAttribute()
    {
        $free = 0;
        $maxPlaces = $this->attributes['max_places'] ?? 0;
        $blocked_inscriptions = $this->countBlockedInscriptions();
        $autolock = 0;
        $limitX100 = $this->limit_x_100 ?? 100;
        $limit = round($maxPlaces * ($limitX100 / 100));

        // calucle autolock
        if ($this->autolock_type !== null) {
            $autolock = $this->sessionTempSlot()->notExpired()->distinct()->count('slot_id');
        }

        // real capacity: count max places - blocked - autolock
        $realCapacity = $maxPlaces - $blocked_inscriptions - $autolock;

        // limit autolock % capacity limit - blocked
        $freeWithLimit = $limit - $blocked_inscriptions;

        $free = max([min($realCapacity, $freeWithLimit), 0]);

        // $this->inscriptions->count() should not be less than 0.
        // we could put an intern alert here if we detect some of
        // these cases.
        $had_inscriptions_loaded = isset($this->relations['inscriptions']);

        // we "forget" inscriptions relation in order to avoid return them
        // throught the API accidentally if they were not set before
        if (!$had_inscriptions_loaded) {
            unset($this->relations['inscriptions']);
        }

        return $free;
    }

    public function getCountValidatedAttribute()
    {
        return $this->inscriptions()->paid()->whereNotNull('checked_at')->count();
    }

    public function getCountValidatedOutAttribute()
    {
        return $this->inscriptions()->paid()->whereNotNull('checked_at')->where('out_event', 1)->count();
    }

    public function getHasPublicRatesAttribute($has_public_rates)
    {
        if (!isset($this->attributes['has_public_rates'])) {
            $this->attributes['has_public_rates'] = (bool) $this->all_rates->filter(function ($r) {
                return $r->is_public;
            })->count();
        }

        return $this->attributes['has_public_rates'];
    }

    public function getHasBookedPacksAttribute()
    {
        return $this->sessionSlot()->where('status_id', '8')->get()->count() > 0;
    }

    public function getRedirectToAttribute()
    {
        if (
            request()->get('brand')
            && $this->brand_id != request()->get('brand')->id
            && get_brand_capability() == 'basic'
            && $frontend_url = Setting::where('brand_id', $this->brand->id)->where('key', 'clients.frontend.url')->first()
        ) {
            return sprintf("%s/%s", trim($frontend_url->value, '/'), "redirect/session/$this->id");
        }

        return null;
    }

    public function getCountAvailableWebPositionsAttribute()
    {
        // Inicialización de variables
        $available = 0;
        $free = 0;
        $maxPlaces = $this->attributes['max_places'] ?? 0;

        // Contamos las posiciones bloqueadas e inscripciones
        $blocked_inscriptions = $this->countBlockedInscriptions();

        // Obtener todas las session slots en una sola consulta con los datos necesarios
        $sessionSlots = $this->sessionSlot()
            ->select('status_id', 'slot_id')
            ->get();

        $blocked_session_slots = $sessionSlots->where('status_id', '!=', 2)->where('status_id', '!=', 6);
        $blocked_session_slot_count = $blocked_session_slots->count();

        // Optimización: reducir la cantidad de plucks y condicionales
        $sold_blocked_session_slot_with_status = $this->inscriptions()
            ->paid()
            ->where('session_id', $this->id)
            ->whereIn('slot_id', $blocked_session_slots->pluck('slot_id'))
            ->count();

        // Cálculo de los bloqueados
        $blocked_session_slot = $blocked_session_slot_count - $sold_blocked_session_slot_with_status;

        // Optimización: obtener las session slots vendidas en una sola consulta
        $blocked_session_slot_sell = $this->sessionSlot()
            ->where('status_id', '2')
            ->count();

        // Autolock y capacidad límite
        $autolock = 0;
        $limitX100 = $this->limit_x_100 ?? 100;
        $limit = round($maxPlaces * ($limitX100 / 100));

        // Cálculo de tarifas disponibles
        $ratesPublic = $this->all_rates()->where('is_public', true)->get();
        foreach ($ratesPublic as $rate) {
            $available += $this->getAvailableFromRateSessio($rate);
        }

        // Optimización: calculo autolock si es necesario
        if ($this->autolock_type !== null) {
            $autolock = $this->sessionTempSlot()->notExpired()->distinct()->count('slot_id');
        }

        // Capacidad real: max places - inscripciones bloqueadas - autolock
        $realCapacity = $maxPlaces - $blocked_inscriptions - $autolock;

        // Capacidad con límite
        $freeWithLimit = $limit - $blocked_inscriptions;

        // Capacidad web: asumiendo que ahora son disponibles por tarifa
        $webCapacity = $available;

        // Validación: Comprobamos diferencia entre butacas en estado 'vendido' y las inscripciones vendidas
        $sell_status_slot = ($blocked_session_slot_sell > $blocked_inscriptions) ? $blocked_session_slot_sell - $blocked_inscriptions : 0;

        // Capacidad dependiendo de las butacas bloqueadas en el sessionSlot
        $sessionSlotBlocked = $maxPlaces - $blocked_session_slot - $blocked_inscriptions - $sell_status_slot;

        // Calcular la cantidad libre y asegurar que no sea menor que 0
        $free = max([min($realCapacity, $freeWithLimit, $webCapacity, $sessionSlotBlocked), 0]);

        // Verificamos si las inscripciones están cargadas previamente
        $had_inscriptions_loaded = isset($this->relations['inscriptions']);

        // Eliminamos la relación para evitar retornarla accidentalmente a través de la API
        if (!$had_inscriptions_loaded) {
            unset($this->relations['inscriptions']);
        }

        return $free;
    }

    public function getAvailableFromRateSessio($rate)
    {

        $confirmed_inscriptions = $this->inscriptions()
            ->paid()
            ->where('rate_id', $rate->rate_id)
            ->count();

        $not_expired_carts = $this->inscriptions()
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->whereNull('carts.confirmation_code')
            ->where('inscriptions.rate_id', $rate->rate_id)
            ->where('carts.expires_on', '>', \Carbon\Carbon::now()->subMinutes($this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE)))
            ->count();

        $sessionTempSlot = $this->inscriptions()
            ->join('session_temp_slot', 'session_temp_slot.inscription_id', '=', 'inscriptions.id')
            ->where('inscriptions.rate_id', $rate->rate_id)
            ->where('session_temp_slot.expires_on', '>', \Carbon\Carbon::now()->subMinutes($this->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE)))
            ->count();

        return $rate->max_on_sale - ($confirmed_inscriptions + $not_expired_carts + $sessionTempSlot);
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
