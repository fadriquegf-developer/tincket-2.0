<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Session;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use App\Observers\InscriptionObserver;
use App\Repositories\InscriptionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Inscription extends BaseModel
{
    use OwnedModelTrait;
    use CrudTrait;
    use SoftDeletes;
    use LogsActivity;
    use SetsBrandOnCreate;

    protected $dates = ['checked_at'];
    protected $with = ['session', 'rate', 'slot'];

    protected $fillable = [
        'cart_id',
        'brand_id',
        'session_id',
        'rate_id',
        'gift_card_id',
        'barcode',
        'code',
        'price',
        'price_sold',
        'checked_at',
        'out_event',
        'created_at',
        'updated_at',
        'deleted_at',
        'deleted_user_id',
        'metadata',
        'group_pack_id',
        'slot_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_sold' => 'decimal:2',
        'out_event' => 'boolean',
        'metadata' => 'array',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        Inscription::observe(InscriptionObserver::class);
    }

    protected static function booted()
    {
        // Aplicar BrandScope automáticamente solo si existe brand_id
        if (get_brand_capability() !== 'engine' && Schema::hasColumn('inscriptions', 'brand_id')) {
            static::addGlobalScope(new BrandScope());
        }
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
        return $this->belongsTo(Session::class)
            ->withoutGlobalScope(BrandScope::class)
            ->withTrashed();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
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
        return $this->belongsTo(Rate::class)
            ->withoutGlobalScope(BrandScope::class)
            ->withTrashed();
    }

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    // VERSIÓN OPTIMIZADA: Ya no necesita hacer JOIN con sessions para filtrar por brand
    public function scopeForBrand(Builder $q, Brand $brand): Builder
    {
        return $q->with(['cart.confirmedPayment'])
            ->where('inscriptions.brand_id', $brand->id)
            ->whereHas(
                'cart.confirmedPayment',
                fn($p) => $p->whereNotNull('paid_at')
            )
            ->select('inscriptions.*');
    }

    // VERSIÓN SÚPER OPTIMIZADA: Usando brand_id directo sin necesidad de verificar sessions
    public function scopeForBrandFast(Builder $q, Brand $brand): Builder
    {
        return $q
            ->join('payments', function ($j) {
                $j->on('payments.cart_id', '=', 'inscriptions.cart_id')
                    ->whereNull('payments.deleted_at')
                    ->whereNotNull('payments.paid_at');
            })
            ->where('inscriptions.brand_id', $brand->id)
            ->distinct('inscriptions.id');
    }

    public static function withJoinedData($filters = [])
    {
        return app(InscriptionRepository::class)->getWithJoinedData($filters);
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
        // Cargar todo de una vez si no está cargado
        $this->loadMissing([
            'group_pack.pack',
            'session.event',
            'brand'
        ]);

        $banner = $this->group_pack?->pack?->banner
            ?? $this->session?->banner
            ?? $this->session?->event?->banner
            ?? $this->brand?->banner;

        // Si existe un banner, asegurar que tenga /storage/
        if ($banner && !str_starts_with($banner, 'storage/') && !str_starts_with($banner, '/storage/')) {
            $banner = '/storage/' . ltrim($banner, '/');
        }

        return $banner;
    }

    public function getPdfNameAttribute($value)
    {
        if (!$value) {
            // filename has pattern: "BRANDID"-"YYYYMMDD OF SESSION"-"CONFIRM ORDER CODE"-"SUBSTR(INSCRIPTION BARCODE, 8)".pdf
            $value = sprintf(
                "%s-%s-%s-%s.pdf",
                $this->brand_id, // Usar brand_id directamente
                $this->session->starts_on->format('Ymd'),
                $this->cart->confirmation_code,
                strtoupper(substr($this->barcode, 0, 8))
            );
        }

        return $value;
    }

    public function getLogo()
    {
        $cartBrand = $this->brand;
        $defaultLogo = $cartBrand->logo;

        // check if event has custom logo
        $customLogo = $this->session->event->custom_logo;

        // priority logo who sell event(cart owner)
        if (!$customLogo || $cartBrand->id !== $this->session->event->brand->id) {
            if (!str_starts_with($defaultLogo, 'storage/') && !str_starts_with($defaultLogo, '/storage/')) {
                $defaultLogo = '/storage/' . ltrim($defaultLogo, '/');
            }
            return $defaultLogo;
        }

        //  Manejar correctamente el prefijo 'uploads/'
        $logoPath = $customLogo;

        // Si ya empieza con 'uploads/', no añadirlo de nuevo
        if (!str_starts_with($logoPath, 'uploads/')) {
            $logoPath = 'uploads/' . ltrim($logoPath, '/');
        }

        return brand_asset(\Storage::url($logoPath), $cartBrand);
    }

    // Añadir en Inscription.php

    /**
     * Scope para cargar todas las relaciones necesarias de forma eficiente
     */
    public function scopeWithFullDetails($query)
    {
        return $query->with([
            'session' => function ($q) {
                $q->select('id', 'name', 'starts_on', 'event_id');
            },
            'session.event' => function ($q) {
                $q->select('id', 'name');
            },
            'cart' => function ($q) {
                $q->select('id', 'confirmation_code', 'client_id', 'brand_id');
            },
            'cart.client' => function ($q) {
                $q->select('id', 'name', 'surname', 'email');
            },
            'rate' => function ($q) {
                $q->select('id', 'name');
            },
            'slot' => function ($q) {
                $q->select('id', 'name', 'zone_id');
            }
        ]);
    }

    /**
     * Scope para inscripciones pagadas
     */
    public function scopePaid($query)
    {
        return $query->whereHas('cart', function ($q) {
            $q->withoutGlobalScope(BrandScope::class)
                ->whereNotNull('confirmation_code');
        });
    }

    /**
     * Scope para inscripciones pendientes
     */
    public function scopePending($query)
    {
        return $query->whereHas('cart', function ($q) {
            $q->whereNull('confirmation_code')
                ->where('expires_on', '>', now());
        });
    }

    /**
     * Obtener slot con session_id como pivot
     * Solo usar cuando realmente necesites pivot_session_id
     */
    public function getSlotWithPivotAttribute()
    {
        if (!$this->slot) {
            return null;
        }

        $slot = clone $this->slot;
        $slot->pivot_session_id = $this->session_id;
        return $slot;
    }

    /**
     * text used in app validation
     */
    public function apiTextRateName()
    {
        $isPack = $this->group_pack_id !== null;
        $name = $isPack ? $this->group_pack->pack->name : $this->rate->name;
        $type = $isPack ? trans('backend.pack.pack') : trans('backend.rate.rate');

        return '<br><b>' . $type . '</b>: ' . $name;
    }
}
