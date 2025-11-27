<?php

namespace App\Models;

use Carbon\Carbon;
use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\IsClassifiable;
use App\Traits\HasTranslations;
use App\Traits\OwnedModelTrait;
use App\Observers\EventObserver;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intervention\Image\Laravel\Facades\Image;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\Setting;

class Event extends BaseModel
{

    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use CrudTrait;
    use SoftDeletes;
    use LogsActivity;
    use HasTranslations;
    use IsClassifiable;
    use OwnedModelTrait;

    protected $hidden = [];
    protected $fillable = [
        'name', // t
        'slug', // t
        'description', // t
        'image',
        'banner',
        'images',
        'email',
        'phone',
        'site',
        'social',
        'publish_on',
        'tags', //t
        'lead', //t
        'metadata', //t
        'custom_logo',
        'custom_text',
        'show_calendar',
        'full_width_calendar',
        'hide_exhausted_sessions',
        'enable_gift_card',
        'price_gift_card',
        'gift_card_text', // t
        'gift_card_email_text', // t
        'gift_card_legal_text', // t
        'gift_card_footer_text',
        'validate_all_event',
        'is_active',
        'brand_id',
        'user_id',
    ];
    public $translatable = [
        'name',
        'slug',
        'description',
        'tags',
        'lead',
        'metadata',
        'custom_text',
        'gift_card_text',
        'gift_card_email_text',
        'gift_card_legal_text',
        'gift_card_footer_text'
    ];
    protected $appends = [
        'redirect_to',
    ];
    protected $casts = [
        'images' => 'array',
        'validate_all_event' => 'boolean',
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(EventObserver::class);
    }

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        static::saved(function (self $event) {
            $event->relocateTempUploads();
        });
    }

    /* public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->hasProminentImage(true);
    } */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function sessions_order()
    {
        return $this->sessions()->orderBy('starts_on', 'DESC');
    }

    public function salesFromStats()
    {
        return \App\Models\StatsSales::query()
            ->leftJoin('inscriptions', 'inscriptions.id', '=', 'stats_sales.inscription_id')
            ->join('sessions', 'sessions.id', '=', 'stats_sales.session_id')
            ->leftJoin('clients', 'clients.id', '=', 'stats_sales.client_id')
            ->leftJoin('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->leftJoin('rates', 'rates.id', '=', 'inscriptions.rate_id')
            ->where('stats_sales.event_id', $this->id)
            ->select(
                'stats_sales.*',
                'inscriptions.id as inscription_id',
                'inscriptions.price_sold',
                'clients.email',
                'clients.phone',
                'clients.mobile_phone',
                'carts.confirmation_code as cart_confirmation_code',
                'carts.seller_type',
                'sessions.starts_on as session_start',
                'rates.name as rate_name'
            )
            ->orderBy('stats_sales.created_at', 'DESC')
            ->get();
    }

    public function firstSession()
    {
        return $this->hasOne(Session::class)
            ->orderBy('starts_on', 'asc');
    }

    public function lastSession()
    {
        return $this->hasOne(Session::class)
            ->orderBy('starts_on', 'desc');
    }

    public function scopePublished($query)
    {
        return $query->where('publish_on', '<', Carbon::now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        $sub = \DB::table('sessions')
            ->select('sessions.starts_on')
            ->whereRaw('sessions.event_id = events.id')
            ->orderByDesc('sessions.starts_on')
            ->limit(1)
            ->toSql();

        return $query
            ->addSelect(\DB::raw("($sub) as last_session"))
            ->orderByDesc('last_session');
    }

    public function scopeFromNow(Builder $query): Builder
    {
        return $query->whereHas('sessions', function (Builder $q) {
            $q->where('ends_on', '>', Carbon::now());
        });
    }

    public function next_sessions()
    {
        return $this->sessions()
            ->where('ends_on', '>', Carbon::now())
            ->where('visibility', 1)
            ->orderBy('starts_on', 'ASC');
    }

    public function sessions_no_finished()
    {
        return $this->sessions()
            ->where('ends_on', '>', Carbon::now())
            ->orderBy('starts_on', 'ASC');
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

        // Si el evento pertenece a un brand diferente al actual
        if ($this->brand_id != $currentBrand->id) {
            return $this->getRedirectTo();
        }

        return null;
    }

    /**
     * Get url event from the event's own brand frontend
     * 
     * @return string|null
     */
    public function getRedirectTo()
    {
        $eventBrand = $this->brand;

        if (!$eventBrand) {
            return null;
        }

        // Cargar capability si no está cargada
        if (!$eventBrand->relationLoaded('capability')) {
            $eventBrand->load('capability');
        }

        $capability = $eventBrand->capability;

        // Verificar el capability del brand DEL EVENTO
        if ($capability && $capability->code_name === 'basic') {
            $frontend_url = Setting::withoutGlobalScope(BrandScope::class)
                ->where('brand_id', $eventBrand->id)
                ->where('key', 'clients.frontend.url')
                ->first();

            if ($frontend_url) {
                return sprintf(
                    "%s/redirect/event/%s",
                    rtrim($frontend_url->value, '/'),
                    $this->id
                );
            }
        }

        return null;
    }

    public function getShowEventButton()
    {

        $frontendUrl = rtrim(brand_setting('clients.frontend.url'), '/');
        $url = "{$frontendUrl}/redirect/event/{$this->id}";

        return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-link pr-0" data-style="zoom-in">
                <span class="ladda-label">
                    <i class="la la-eye" aria-hidden="true"></i> ' . __('backend.events.show_event') . '
                </span>
            </a>';
    }



    public function getCreateSessionButton()
    {
        $url = backpack_url('session/create') . '?event_id=' . $this->id;

        return '
            <a href="' . $url . '" class="btn btn-sm btn-link">
                <i class="la la-plus"></i> Crear ' . __('backend.session.session') . '
            </a>
        ';
    }

    public function getCloneButton()
    {

        $url = route('event.clone', ['id' => $this->id]);
        $text = app()->getLocale() === 'en' ? 'Clone' : 'Clonar';

        return '<a href="' . $url . '" class="btn btn-sm btn-link pr-0" data-style="zoom-in">
                <span class="ladda-label">
                    <i class="la la-copy"></i> ' . $text . '
                </span>
            </a>';
    }

    public function getFirstSessionAttribute()
    {
        return $this->sessions()->where('visibility', 1)->orderBy('starts_on', 'ASC')->first();
    }

    public function getLastSessionAttribute()
    {
        return $this->sessions()->where('visibility', 1)->orderBy('starts_on', 'DESC')->first();
    }

    public function getNextSessionAttribute()
    {
        return $this->next_sessions()->with(['space.location'])->first();
    }

    public function getSessionNoFinishedAttribute()
    {
        return $this->next_sessions()->with(['space.location'])->first();
    }

    protected function relocateTempUploads(): void
    {
        $singleImageFields = ['image', 'custom_logo', 'banner'];

        $brand = get_current_brand()->code_name;
        $baseDir = "uploads/{$brand}/event/{$this->id}";     // destino final
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
            $this->saveQuietly();   // evita eventos/updated_at innecesarios
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

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        // Si ya es URL completa
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Convertir a URL absoluta
        $path = str_replace('\\', '/', $this->image);

        if (str_starts_with($path, 'storage/')) {
            return url($path);
        }

        if (str_starts_with($path, 'uploads/')) {
            return url('storage/' . $path);
        }

        return url('storage/' . ltrim($path, '/'));
    }

    /**
     * Obtener URL absoluta del banner para emails
     */
    public function getBannerUrlAttribute(): string
    {
        if (!$this->banner) {
            return '';
        }

        if (filter_var($this->banner, FILTER_VALIDATE_URL)) {
            return $this->banner;
        }

        if (str_starts_with($this->banner, 'storage/')) {
            return url($this->banner);
        }

        return url('storage/' . ltrim($this->banner, '/'));
    }

    public function getTextWithVariables($text, $gift)
    {
        $vars = array(
            '{$code}' => $gift->code,
            '{$name}' => $this->name,
            '{$friend}' => $gift->cart->client->name ?? ''
        );

        return strtr($text, $vars);
    }
}
