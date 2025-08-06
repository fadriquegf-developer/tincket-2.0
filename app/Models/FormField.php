<?php

namespace App\Models;

use App\Models\Brand;
use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\HasTranslations;
class FormField extends BaseModel
{

    use CrudTrait;
    use SetsBrandOnCreate;
    use HasTranslations;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use LogsActivity;
    use OwnedModelTrait;

    protected $fillable = ['label', 'type', 'weight', 'config', 'is_editable', 'name', 'brand_id'];
    protected $fakeColumns = ['config'];
    protected $appends = ['required'];
    protected $casts = ['config' => 'array'];

    public $translatable = [
        'label',
        'name'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BrandScope());

        static::creating(function ($formField) {
            if (empty($formField->name)) {
                $formField->name = Str::slug($formField->label, '-');
            }
        });

        static::updating(function ($formField) {
            $formField->name = Str::slug($formField->label, '-');
        });
    }

    public function getConfigAttribute($value)
    {
        $config = json_decode($value);
        if (isset($config->rules)) {
            $config->required = 1;
        }
        unset($config->rules);

        return $config;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function forms()
    {
        return $this->belongsToMany(Form::class, 'form_form_field', 'form_field_id', 'form_id');
    }


    public function getRequiredAttribute($value)
    {
        return $value;
    }


    /* public function setOptionsAttribute($value)
    {
        if (!is_array($value)) {
            $value = json_decode($value, true) ?? [];
        }

        if (in_array($this->type ?? 'text', ['select', 'radio', 'checkbox'])) {
            $value = array_map(function ($option) {
                if (isset($option['value'])) {
                    $option['key'] = Str::studly(Str::ascii($option['value']));
                }
                return $option;
            }, $value);
            $this->attributes['options'] = json_encode($value);
        } else {
            $this->attributes['options'] = null;
        }
    } */
    /* public function setNameAttribute($name)
    {
        if (!isset($name) || !$name)
        {
            $this->attributes['name'] = Str::slug($this->label);
        } else
        {
            $this->attributes['name'] = Str::slug($name);
        }

        $this->attributes['name'];
    }  */
}
