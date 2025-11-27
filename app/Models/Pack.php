<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class Pack extends BaseModel
{

    use HasTranslations;
    use CrudTrait;
    use SetsBrandOnCreate;
    use SoftDeletes;
    use LogsActivity;
    use OwnedModelTrait;

    public $translatable = ['name', 'description', 'slug'];
    public $fillable = [
        'starts_on',
        'ends_on',
        'min_per_cart',
        'max_per_cart',
        'round_to_nearest',
        'one_session_x_event',
        'color',
        'banner',
        'custom_logo',
        'bg_color',
        'cart_rounded',
        'brand_id',
    ];
    public $dates = ['starts_on', 'ends_on'];

    protected $appends = [
        'is_all_sessions',
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function rules()
    {
        return $this->hasMany(PackRule::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(Session::class);
    }

    public function next_sessions()
    {
        return $this->sessions()
            ->where('starts_on', '>', \Carbon\Carbon::now())
            ->where('inscription_starts_on', '<', \Carbon\Carbon::now())
            ->where('inscription_ends_on', '>', \Carbon\Carbon::now());
    }

    /**
     * Returns the events of the pack through the Sessions it has
     * 
     * Because Pack-Event is a many to many through one-many relation 
     * (Pack - Sessions - Event) we need some hack because Laravel does not
     * support this kind of relation.
     * 
     * We could implement our own type of abstract \Illuminate\Database\Eloquent\Relations\Relation
     * or access to events throught this Accessor which requires one single query.
     * 
     * Another way would be load(sessions.event) and pluck the event inside
     * an events variable. <strong>More queries are required</strong> for this way instead of one.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEventsAttribute()
    {
        if (!$this->relationLoaded('events')) {
            $id = $this->id;
            $events = Event::distinct()
                ->join('sessions', 'events.id', '=', 'sessions.event_id')
                ->whereHas('sessions.packs', function ($query) use ($id) {
                    return $query->where('packs.id', $id);
                })
                ->with([
                    'next_sessions' => function ($query) use ($id) {
                        $query->has('allRates')
                            ->whereHas('packs', function ($query) use ($id) {
                                $query->where('packs.id', $id);
                            })
                            // 🔧 FIX: Cargar space para el layout
                            ->with(['space', 'event']);
                    }
                ])
                ->get(['events.*']);

            $this->setRelation('events', $events);
        }

        return $this->getRelation('events');
    }

    public function scopePublished($query)
    {
        return $query->where('starts_on', '<', \Carbon\Carbon::now());
    }

    public function getIsAllSessionsAttribute()
    {
        $rules = $this->rules;
        return $rules->count() === 1 && $rules->first()->all_sessions === 1;
    }


    /**
     * Slug of Pack never will be blank. It will be set as the Slug User Input 
     * or pack's title slugged otherwise
     * 
     * @param string $slug
     * @return string
     */
    public function setSlugAttribute($slug)
    {
        if (!isset($slug) || !$slug) {
            $this->attributes['slug'] = \Illuminate\Support\Str::slug($this->name);
        } else {
            $this->attributes['slug'] = \Illuminate\Support\Str::slug($slug);
        }

        return $this->attributes['slug'];
    }
}
