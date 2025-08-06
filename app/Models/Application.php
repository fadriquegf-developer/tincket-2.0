<?php

namespace App\Models;

use App\Models\Brand;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Application extends BaseModel
{

    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use LogsActivity;

    protected $hidden = [
        'id',
    ];
    protected $fillable = [
        'code_name',
        'allowed_host',
        'key',
        'brand_id'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * $this->name is an alias of $this->code_name
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->code_name;
    }

}
