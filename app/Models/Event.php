<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\HasImages;
use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\IsClassifiable;
use App\Traits\OwnedModelTrait;
use App\Observers\EventObserver;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intervention\Image\Laravel\Facades\Image;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;
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
            ->where('stats_sales.event_id', $this->id)
            ->select('stats_sales.*', 'inscriptions.*', 'clients.email', 'clients.phone', 'carts.confirmation_code as cart_confirmation_code', 'sessions.starts_on as session_start')
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
        $brand_id = get_current_brand()->id ?? null;

        if ($this->brand_id != $brand_id) {
            return $this->getRedirectTo();
        }

        return null;
    }

    /**
     * Get url event "client.frontend.url"
     * 
     * @return string
     */
    public function getRedirectTo()
    {
        $brand = get_current_brand();
        if (
            get_brand_capability() == 'basic'
            && $frontend_url = Setting::where('brand_id', $brand->id)->where('key', 'clients.frontend.url')->first()
        ) {
            return sprintf("%s/%s", trim($frontend_url->value, '/'), "redirect/event/$this->id");
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
                <i class="la la-plus"></i> Crear Sesión
            </a>
        ';
    }

    public function getCloneButton()
    {

        $url = route('event.clone', ['id' => $this->id]);

        return '<a href="' . $url . '" class="btn btn-sm btn-link pr-0" data-style="zoom-in">
                <span class="ladda-label">
                    <i class="la la-copy"></i> ' . 'Clonar' . '
                </span>
            </a>';
    }


    public function setImageAttribute($value)
    {
        if (!$value || $value === $this->getOriginal('image')) {
            return;
        }

        if (!$value || !is_string($value) || !Storage::disk('public')->exists($value)) {
            $this->attributes['image'] = $value;
            return;
        }

        try {
            $brand = get_current_brand()->code_name;
            $eventId = $this->attributes['id'] ?? Str::uuid();
            $basePath = "uploads/{$brand}/event/{$eventId}/";

            $img = Image::read(Storage::disk('public')->path($value));
            $fileName = 'logo-' . Str::uuid() . '.webp';

            $maxWidth = 1200;
            if ($img->width() > $maxWidth) {
                $img = $img->scale(width: $maxWidth);
            }

            // Guardar principal
            Storage::disk('public')->put($basePath . $fileName, $img->encode(new WebpEncoder(quality: 80)));

            // Versiones
            $md = (clone $img)->scale(width: 800);
            $sm = (clone $img)->scale(width: 300);
            Storage::disk('public')->put($basePath . 'md-' . $fileName, $md->encode(new WebpEncoder(quality: 80)));
            Storage::disk('public')->put($basePath . 'sm-' . $fileName, $sm->encode(new WebpEncoder(quality: 80)));

            // Borrar original temporal
            Storage::disk('public')->delete($value);

            $this->attributes['image'] = $basePath . $fileName;
        } catch (\Throwable $e) {
            // Fallback por si algo sale mal
            $this->attributes['image'] = $value;
        }
    }


    public function setImagesAttribute($value)
    {
        // Si llega null, lo dejamos como array vacío
        if (is_null($value)) {
            $this->attributes['images'] = json_encode([]);
            return;
        }

        // --- 1) Si llega string, intentamos decodificarlo como JSON ---
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                // No era JSON válido → dejamos $value como array vacío
                $value = [];
            }
        }

        // --- 2) Si llega stdClass (o cualquier objeto), convertimos a array ---
        if ($value instanceof \stdClass) {
            // Convertimos recursivamente a array; en este caso es un objeto con keys "1","2",…
            $value = (array) $value;
        }

        // --- 3) Finalmente, aseguramos que sea array indexado numéricamente ---
        if (is_array($value)) {
            // Si vinieran claves no numéricas, hacemos array_values()
            $arrayOnly = array_values($value);
            $this->attributes['images'] = json_encode($arrayOnly);
            return;
        }

        // Si nada de lo anterior, guardamos array vacío
        $this->attributes['images'] = json_encode([]);
    }


    public function setCustomLogoAttribute($value)
    {
        if (!$value || $value === $this->getOriginal('custom_logo')) {
            return;
        }

        // Si ya está en formato final y sin path, lo ignoramos
        if (Str::startsWith($value, 'custom-logo-') && Str::endsWith($value, '.webp') && !Str::contains($value, '/')) {
            return;
        }

        try {
            $brand = get_current_brand()->code_name;
            $eventId = $this->attributes['id'] ?? Str::uuid();
            $basePath = "uploads/{$brand}/event/{$eventId}/";
            $fileName = 'custom-logo-' . Str::uuid() . '.webp';
            $finalPath = $basePath . $fileName;

            // Procesar sólo si el valor es un path existente en disco o un objeto de imagen
            $isDiskFile = is_string($value) && Storage::disk('public')->exists($value);
            $isUpload = is_object($value) || (is_string($value) && file_exists($value));

            if (!$isDiskFile && !$isUpload) {
                // No es una imagen válida para procesar
                $this->attributes['custom_logo'] = $value;
                return;
            }

            // Leer imagen desde el path
            $image = $isDiskFile
                ? Image::read(Storage::disk('public')->path($value))
                : Image::read($value);

            // Redimensionar si hace falta
            if ($image->width() > 1200) {
                $image = $image->scale(width: 1200);
            }
            

            Storage::disk('public')->put($finalPath, $image->encode(new WebpEncoder(quality: 80)));

            $this->attributes['custom_logo'] = $finalPath;

            // Eliminar imagen temporal original si es de disco
            if ($isDiskFile) {
                Storage::disk('public')->delete($value);
            }

        } catch (\Throwable $e) {
            \Log::error("Error al procesar custom_logo: " . $e->getMessage());
            $this->attributes['custom_logo'] = $value;
        }
    }

    public function setBannerAttribute($value)
    {
        if (!$value || $value === $this->getOriginal('banner')) {
            return;
        }

        // Si ya está en formato final y sin path, lo ignoramos
        if (Str::startsWith($value, 'banner-') && Str::endsWith($value, '.webp') && !Str::contains($value, '/')) {
            return;
        }

        try {
            $brand = get_current_brand()->code_name;
            $eventId = $this->attributes['id'] ?? Str::uuid();
            $basePath = "uploads/{$brand}/event/{$eventId}/";
            $fileName = 'banner-' . Str::uuid() . '.webp';
            $finalPath = $basePath . $fileName;

            // Procesar sólo si el valor es un path existente en disco o un objeto de imagen
            $isDiskFile = is_string($value) && Storage::disk('public')->exists($value);
            $isUpload = is_object($value) || (is_string($value) && file_exists($value));

            if (!$isDiskFile && !$isUpload) {
                // No es una imagen válida para procesar
                $this->attributes['banner'] = $value;
                return;
            }

            // Leer imagen desde el path
            $image = $isDiskFile
                ? Image::read(Storage::disk('public')->path($value))
                : Image::read($value);


            Storage::disk('public')->put($finalPath, $image->encode(new WebpEncoder(quality: 80)));

            $this->attributes['banner'] = $finalPath;

            // Eliminar imagen temporal original si es de disco
            if ($isDiskFile) {
                Storage::disk('public')->delete($value);
            }

        } catch (\Throwable $e) {
            \Log::error("Error al procesar banner: " . $e->getMessage());
            $this->attributes['banner'] = $value;
        }
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

}
