<?php

namespace App\Models;

use App\Models\Brand;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Code extends BaseModel
{

    use CrudTrait;
    use OwnedModelTrait;
    use SetsBrandOnCreate;
    use LogsActivity;

    protected $table = 'codes';

    protected $hidden = [];
    protected $fillable = [
        'keycode',
        'brand_id',
        'promotor_id'
    ];
    public $appends = ['brand_name', 'promotor_name'];

    protected static function booted()
    {
        static::addGlobalScope(new BrandScope());
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function promotor()
    {
        return $this->belongsTo(Brand::class, 'promotor_id');
    }

    public function getBrandNameAttribute()
    {
        return $this->brand->name;
    }

    public function getPromotorNameAttribute()
    {
        if ($this->promotor)
            return $this->promotor->name;
        return null;
    }

    public function getPromotorURL()
    {
        if ($this->promotor)
            return 'https://' . $this->promotor->allowed_host;
        return '#';
    }


    function generateRandomString($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateCodeButton(){
        return '<a href="code/generate-code" class="btn btn-primary">
                <i class="la la-cog me-1"></i> ' . __('backend.code.generate_code').'
            </a>';
    }
}
