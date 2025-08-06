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
        'images' => 'array',
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

    }

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
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

    public function optimiceImages()
    {
        return '<a href="' . route('post.optimice-images') . '" class="btn btn-primary"> <i class="la la-image me-1"></i> ' . __('backend.post.optimice_images') . '</a>';
    }

    public function getSlugOrNameAttribute()
    {
        if ($this->slug != '') {
            return $this->slug;
        }

        return $this->name;
    }

    public function getImageAttribute($value)
    {
        return $value ? \Storage::url($value) : null;
    }

    public function setGalleryAttribute($value)
    {
        // Si llega null, lo dejamos como array vacío
        if (is_null($value)) {
            $this->attributes['gallery'] = json_encode([]);
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
            $this->attributes['gallery'] = json_encode($arrayOnly);
            return;
        }

        // Si nada de lo anterior, guardamos array vacío
        $this->attributes['gallery'] = json_encode([]);
    }

}
