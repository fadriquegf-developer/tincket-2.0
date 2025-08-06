<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Province extends BaseModel
{
    use CrudTrait;

    protected $fillable = [
        'name'
    ];

    public function regions()
    {
        return $this->hasMany(Region::class);
    }
}
