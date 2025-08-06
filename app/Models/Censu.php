<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Censu extends BaseModel
{
    use CrudTrait;
    use SetsBrandOnCreate;
    use OwnedModelTrait;
    use LogsActivity;

    protected $hidden = [];
    protected $fillable = [
        'brand_id',
        'name',
        'code'
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


}
