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

    protected $fillable = ['label', 'type', 'weight', 'config', 'name', 'brand_id', 'field_options', 'field_required'];
    protected $fakeColumns = ['config'];
    protected $casts = ['config' => 'array'];
    protected $attributes = [
        'weight' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->config)) {
                $model->config = [];
            }
        });

        static::saving(function ($model) {
            if (empty($model->config)) {
                $model->config = [];
            }
        });
    }

    public $translatable = [
        'label',
    ];

    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope(new BrandScope());

        static::creating(function ($formField) {
            if (empty($formField->config)) {
                $formField->config = [];
            }

            if (empty($formField->name)) {
                $formField->name = static::generateUniqueName($formField->label, $formField->brand_id);
            }
        });

        static::updating(function ($formField) {
            if (empty($formField->config)) {
                $formField->config = [];
            }

            $originalName = $formField->getOriginal('name');

            if (!empty($originalName) && $formField->name === $originalName) {
                return;
            }

            if (empty($formField->name)) {
                $formField->name = static::generateUniqueName($formField->label, $formField->brand_id);
            }

            if (!empty($formField->name) && $formField->name !== $originalName) {
                $formField->name = Str::slug($formField->name, '_');

                $exists = static::where('brand_id', $formField->brand_id)
                    ->where('name', $formField->name)
                    ->where('id', '!=', $formField->id)
                    ->exists();

                if ($exists) {
                    $formField->name = static::generateUniqueName($formField->name, $formField->brand_id, $formField->id);
                }
            }
        });
    }

    protected static function generateUniqueName($baseText, $brandId = null, $excludeId = null)
    {
        $baseName = Str::slug($baseText, '_');

        if (empty($baseName)) {
            $baseName = 'field';
        }

        $baseName = Str::limit($baseName, 50, '');
        $name = $baseName;
        $counter = 1;

        while (true) {
            $query = static::where('brand_id', $brandId)
                ->where('name', $name);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $counter++;
            $name = $baseName . '_' . $counter;
        }

        return $name;
    }

    public function getConfigAttribute($value)
    {
        if (is_string($value)) {
            $config = json_decode($value, true);
        } else {
            $config = $value;
        }

        if (isset($config['rules'])) {
            $rules = is_array($config['rules']) ? implode('|', $config['rules']) : $config['rules'];
            if (str_contains($rules, 'required')) {
                $config['required'] = true;
            }
        }

        return $config;
    }

    public function setConfigAttribute($value)
    {
        if (is_array($value)) {
            if (isset($value['options']) && is_array($value['options'])) {
                $value['options'] = array_filter($value['options'], function ($option) {
                    return !empty($option['value']) || !empty($option['label']);
                });
                $value['options'] = array_values($value['options']);
            }

            $this->attributes['config'] = json_encode($value);
        } else {
            $this->attributes['config'] = $value;
        }
    }

    /**
     * Accessor para field_options (repeatable)
     */
    public function getFieldOptionsAttribute()
    {
        $config = $this->config ?? [];
        $options = $config['options'] ?? [];
        $currentLocale = app()->getLocale();
        
        return collect($options)->map(function($option) use ($currentLocale) {
            return [
                'value' => $option['value'] ?? '',
                'label' => is_array($option['label'] ?? '') 
                    ? ($option['label'][$currentLocale] ?? $option['label']['ca'] ?? reset($option['label']))
                    : ($option['label'] ?? ''),
            ];
        })->toArray();
    }

    public function getFieldRequiredAttribute()
    {
        $config = $this->config ?? [];
        return filter_var($config['required'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function isRequired()
    {
        $config = $this->config;

        if (isset($config['required']) && $config['required']) {
            return true;
        }

        if (isset($config['rules'])) {
            $rules = is_array($config['rules']) ? implode('|', $config['rules']) : $config['rules'];
            return str_contains($rules, 'required');
        }

        return false;
    }

    public function getValidationRules()
    {
        $config = $this->config;
        $rules = [];

        switch ($this->type) {
            case 'text':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'textarea':
                $rules[] = 'string';
                $rules[] = 'max:5000';
                break;
            case 'date':
                $rules[] = 'date';
                $rules[] = 'date_format:Y-m-d';
                break;
            case 'select':
            case 'radio':
                $validOptions = $this->getValidOptionValues();
                if (!empty($validOptions)) {
                    $rules[] = 'in:' . implode(',', $validOptions);
                }
                break;
            case 'checkbox':
                $rules[] = 'array';
                $validOptions = $this->getValidOptionValues();
                if (!empty($validOptions)) {
                    $rules['*'] = 'in:' . implode(',', $validOptions);
                }
                break;
        }

        if ($this->isRequired()) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        if (isset($config['rules'])) {
            $customRules = is_array($config['rules']) ? $config['rules'] : explode('|', $config['rules']);
            $rules = array_merge($rules, $customRules);
        }

        $rules = array_unique($rules);

        return $rules;
    }

    private function getValidOptionValues()
    {
        $options = $this->getNormalizedOptions();

        return collect($options)
            ->pluck('value')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function forms()
    {
        return $this->belongsToMany(Form::class, 'form_form_field', 'form_field_id', 'form_id')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function answers()
    {
        return $this->hasMany(FormFieldAnswer::class, 'field_id');
    }

    public function canBeAssociatedWithForm(Form $form)
    {
        return $this->brand_id === $form->brand_id;
    }

    public function getTranslatedConfig($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $config = $this->config;

        if (!isset($config['options']) || !is_array($config['options'])) {
            return $config;
        }

        $config['options'] = collect($config['options'])->map(function ($option) use ($locale) {
            $translatedOption = $option;

            if (isset($option['label']) && is_array($option['label'])) {
                $translatedOption['label'] = $option['label'][$locale] ??
                    $option['label'][config('app.fallback_locale')] ??
                    reset($option['label']);
            }

            return $translatedOption;
        })->toArray();

        return $config;
    }

    public function getNormalizedOptions()
    {
        $config = $this->config;

        if (!isset($config['options']) || !is_array($config['options'])) {
            return [];
        }

        return $config['options'];
    }
    
    /**
     * Helper para stripAccents (usado en el controller)
     */
    public static function stripAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
            chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
            chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
            chr(195).chr(167) => 'c', chr(195).chr(177) => 'n',
        ];

        return strtr($string, $chars);
    }
}