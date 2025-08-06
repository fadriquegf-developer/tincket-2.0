<?php

namespace App\Models;

use App\Models\Brand;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Setting extends BaseModel
{

    use CrudTrait;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    protected $fillable = [
        'category',
        'key',
        'value',
        'access',
        'brand_id'
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
