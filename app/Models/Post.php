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
        if (!$this->id || empty($this->image))
            return;

        $brand = get_current_brand()->code_name;
        $disk = Storage::disk('public');

        // Normaliza lo justo: backslashes -> slashes y quita prefijos "storage/"
        $path = str_replace('\\', '/', (string) $this->image);
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8); // "storage/" => rutas relativas al disk 'public'
        } elseif (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        }

        // Solo actuamos si viene de __TEMP__
        if (stripos($path, '/post/temp/') === false)
            return;

        $baseDir = "uploads/{$brand}/post/{$this->id}";
        $srcDir = dirname($path);
        $filename = basename($path);
        $dest = "{$baseDir}/{$filename}";

        $disk->makeDirectory($baseDir);

        // Mover principal
        if ($disk->exists($path)) {
            $disk->move($path, $dest);
        } else {
            return;
        }

        // Mover variantes md- y sm- si existen
        foreach (['md-', 'sm-'] as $pre) {
            $srcVar = "{$srcDir}/{$pre}{$filename}";
            if ($disk->exists($srcVar)) {
                $disk->move($srcVar, "{$baseDir}/{$pre}{$filename}");
            }
        }

        // Actualiza el campo sin re-disparar eventos
        if ($this->image !== $dest) {
            $this->image = $dest;
            $this->saveQuietly();
        }
    }

}
