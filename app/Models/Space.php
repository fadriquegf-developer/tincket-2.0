<?php

namespace App\Models;

use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\HasTranslations;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Space extends BaseModel
{
    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use CrudTrait;
    use HasTranslations;
    use SoftDeletes;
    use LogsActivity;
    use OwnedModelTrait;

    protected $hidden = [];
    protected $fillable = [
        'name', // t
        'slug', // t
        'description', // t
        'capacity',
        'location_id',
        'svg_path',
        'hide',
        'zoom',
        'user_id',
        'brand_id'
    ];
    public $translatable = [
        'name',
        'slug',
        'description',
    ];
    public $appends = [
        'svg_host_path',
        'name_city'
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function getNameCityAttribute()
    {
        $location = $this->location;
        $cityName = $location && $location->city ? $location->city->name : __('Sin localidad');

        return "{$this->name} - {$cityName}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function next_sessions()
    {
        return $this->sessions()
            ->where('starts_on', '>', \Carbon\Carbon::now())
            ->where('visibility', 1)
            ->orderBy('starts_on', 'ASC');
    }

    public function slots()
    {
        return $this->hasMany(Slot::class);
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }


    public function getSvgHostPathAttribute()
    {
        return \Storage::url($this->svg_path);
    }

}
