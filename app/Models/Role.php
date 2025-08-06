<?php

namespace App\Models;

use App\Models\Brand;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Spatie\Permission\Models\Role as OriginalRole;

class Role extends OriginalRole
{
    use CrudTrait;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    protected $fillable = ['name', 'guard_name', 'brand_id', 'updated_at', 'created_at'];

     /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        /* Fadri comento esto, porque sino en el UserCrudController, no se pasan los roles que no tienen brand_id, que son roles generales para todos */
        /* if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        } */
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
