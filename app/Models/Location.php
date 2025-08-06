<?php

namespace App\Models;

use App\Models\User;
use App\Models\Brand;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsUserOnCreate;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class Location extends BaseModel
{

    use CrudTrait;
    use HasTranslations;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use SetsUserOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    protected $hidden = [];
    protected $fillable = [
        'name', // t
        'slug', // t
        'description', // t
        'address',
        'postal_code',
        'other_info', // t
        'phone1',
        'phone2',
        'email',
        'city_id',
        'brand_id',
        'user_id',
    ];
    public $translatable = [
        'name',
        'slug',
        'description',
        'other_info'
    ];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function spaces()
    {
        return $this->hasMany(Space::class);
    }

    /**
     * Location already has a field city use town instead
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
