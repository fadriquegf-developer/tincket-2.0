<?php

namespace App\Models;

use DateTimeInterface;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\IsClassifiable;
use App\Observers\PostObserver;
use App\Traits\HasTranslations;
use App\Traits\OwnedModelTrait;
use App\Traits\BackpackSluggable;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;

class Post extends BaseModel
{
    use CrudTrait;
    use HasTranslations;
    use IsClassifiable;
    use BackpackSluggable;
    use SluggableScopeHelpers;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;


    protected $dateFormat = 'Y-m-d H:i:s';
    protected $hidden = [];
    protected $fillable = [
        'name', // t
        'slug', // t
        'publish_on',
        'meta_title', // t
        'meta_description', //t
        'lead', //t
        'body', // t
        'images',
        'gallery',
        'brand_id',
    ];
    public $translatable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'lead',
        'body',
    ];

    protected $dates = [
        'publish_on',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'gallery' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(PostObserver::class);
    }

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        static::saved(function (self $post) {
            $post->relocateTempUploads();
        });
    }

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    public function taxonomies()
    {
        return $this->morphToMany(Taxonomy::class, 'classifiable');
    }

    public function getDates()
    {
        return array_merge(parent::getDates(), ['publish_on']);
    }

    public function scopePublished($query)
    {
        return $query->where('publish_on', '<', \Carbon\Carbon::now());
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'slug_or_name',
            ],
        ];
    }

    public function getSlugOrNameAttribute()
    {
        if ($this->slug != '') {
            return $this->slug;
        }

        return $this->name;
    }

    /* public function getImageAttribute($value)
    {
        return $value ? \Storage::url($value) : null;
    } */

    protected function relocateTempUploads(): void
    {
        // Este método ya no es necesario si guardamos directamente en la ruta final
        // Las rutas en BD son del tipo: "uploads/tmf/post/post-image-xxx.webp"
        // Sin embargo, lo dejamos por si acaso hay algún caso edge

        if (!$this->id || empty($this->image)) {
            return;
        }

        // Si la imagen ya está en la ruta correcta, no hacer nada
        // Las rutas correctas son: "uploads/{brand}/post/post-image-xxx.webp"
        if (!str_contains($this->image, '/temp/') && !str_contains($this->image, 'backpack/temp/')) {
            return;
        }

        // Si por alguna razón hay una imagen temporal, moverla
        $brand = get_current_brand()->code_name;
        $disk = Storage::disk('public');

        $path = str_replace('\\', '/', (string) $this->image);
        $filename = basename($path);
        $finalPath = "uploads/{$brand}/post/{$filename}";

        if ($disk->exists($path) && $path !== $finalPath) {
            $disk->move($path, $finalPath);

            // Mover variantes si existen
            $dir = dirname($path);
            foreach (['md-', 'sm-'] as $prefix) {
                $varPath = "{$dir}/{$prefix}{$filename}";
                if ($disk->exists($varPath)) {
                    $disk->move($varPath, "uploads/{$brand}/post/{$prefix}{$filename}");
                }
            }

            $this->image = $finalPath;
            $this->saveQuietly();
        }
    }

    /**
     * Obtener URL absoluta de la imagen para emails
     */
    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return url('images/default-post-image.jpg');
        }

        // Si ya es una URL completa, devolverla
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Si ya tiene el prefijo "storage/", devolverla como está
        if (str_starts_with($this->image, 'storage/')) {
            return url($this->image);
        }

        // Para rutas del tipo "uploads/tmf/post/post-image-xxx.webp"
        // Solo añadir el prefijo "storage/"
        return url('storage/' . ltrim($this->image, '/'));
    }

    /**
     * Mutator para limpiar la ruta de la imagen antes de guardar
     * Previene duplicación de rutas cuando Backpack añade prefijos
     */
    public function setImageAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['image'] = null;
            return;
        }

        // Si es una URL completa, guardarla tal cual
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $this->attributes['image'] = $value;
            return;
        }

        // Normalizar barras
        $value = str_replace('\\', '/', $value);

        // Eliminar prefijos storage/ o /storage/ si existen
        $value = preg_replace('#^/?storage/#', '', $value);

        // Eliminar duplicaciones de ruta
        // Si la ruta contiene "uploads/X/post/uploads/X/post/", limpiarla
        if (preg_match('#(uploads/[^/]+/post/).*?(uploads/[^/]+/post/)#', $value)) {
            $value = preg_replace('#^.*?(uploads/[^/]+/post/)#', '$1', $value);
        }

        $this->attributes['image'] = $value;
    }
}
