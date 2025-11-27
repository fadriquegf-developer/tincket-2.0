<?php

namespace App\Models;

use App\Models\Session;
use App\Models\FormField;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;

class Form extends BaseModel
{

    use CrudTrait;
    use HasTranslations;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use OwnedModelTrait;

    //public $translatable = [];
    protected $table = 'forms';
    protected $fillable = ['name', 'brand_id'];

    protected static function booted()
    {
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    public function form_fields()
    {
        return $this->belongsToMany(FormField::class, 'form_form_field', 'form_id', 'form_field_id')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function rate()
    {
        return $this->hasMany(Session::class);
    }
}
