<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Region extends BaseModel
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'province_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class)->where('status', 1);
    }
}
