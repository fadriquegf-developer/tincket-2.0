<?php

namespace App\Models;

use App\Models\Event;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use \Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use App\Traits\HasTranslations;


class Taxonomy extends BaseModel
{
    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use HasTranslations;
    use CrudTrait;
    use SoftDeletes;
    use LogsActivity;
    use Sluggable, SluggableScopeHelpers;
    use OwnedModelTrait;

    protected $fillable = ['name', 'slug', 'parent_id', 'lft', 'rgt', 'depth', 'active', 'brand_id', 'user_id'];
    public $translatable = ['name', 'slug'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function parent()
    {
        return $this->belongsTo(Taxonomy::class, 'parent_id')->active();
    }

    public function children()
    {
        return $this->hasMany(Taxonomy::class, 'parent_id')->active();
    }

    public function events()
    {
        $brand = get_current_brand() ? get_current_brand() : request()->get('brand');

        // check return self events or allowed partners events
        $partners = $brand->partnershipedChildBrands->pluck('id')->toArray();
        $partners[] = $brand->id;

        return $this->morphedByMany(Event::class, 'classifiable')->whereIn('events.brand_id', $partners);
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

    public function next_events()
    {
        return $this->events()
            ->where('events.publish_on', '<', \Carbon\Carbon::now())
            ->whereHas('sessions', function ($session) {
                $session
                    ->where('ends_on', '>', \Carbon\Carbon::now())
                    ->where('visibility', 1);
            });
    }

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'classifiable')->orderBy('publish_on', 'DESC');
    }

    public function published_posts()
    {
        return $this->posts()->whereNotNull('publish_on');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
