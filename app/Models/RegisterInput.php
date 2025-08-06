<?php

namespace App\Models;

use App\Models\Brand;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class RegisterInput extends BaseModel
{

    use CrudTrait;
    use LogsActivity;

    protected $table = 'register_inputs';

    protected $hidden = [];
    
    protected $fillable = [
        'title',
        'name_form',
        'type', 
    ];

    public function brands()
    {
        return $this->belongsToMany(Brand::class,'brands_register_inputs', 'register_input_id', 'brand_id')->withPivot('required')->withTimestamps();
    }

}
