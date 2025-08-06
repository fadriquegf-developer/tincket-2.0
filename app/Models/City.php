<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class City extends BaseModel
{
    use CrudTrait;
    use LogsActivity;

    protected $fillable = [
        'name',
        'city_id',
        'region_id',
        'province_id',
        'zip'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function city()
    {
        return $this->belongsTo(self::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function location()
    {
        return $this->hasMany(Location::class);
    }
}
