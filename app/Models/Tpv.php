<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Tpv extends BaseModel
{
    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use OwnedModelTrait;
    use SetsBrandOnCreate;
    use LogsActivity;

    /** List of all accepted TPV. This must match with 
     * omnipay library name */
    const TPV_TYPES = [
        'Sermepa' => 'Sermepa',
    ];

    public $fillable = ['name', 'config', 'omnipay_type', 'brand_id'];
    protected $casts = [
        'config' => 'array',
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
