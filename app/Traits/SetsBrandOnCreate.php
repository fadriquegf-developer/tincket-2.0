<?php

namespace App\Traits;

trait SetsBrandOnCreate
{
    public static function bootSetsBrandOnCreate()
    {
        // Para relaciones 1:n o 1:1: asigna el brand_id al crear el modelo
        static::creating(function ($model) {
            // Solo se asigna si el modelo posee el campo 'brand_id'
            if (in_array('brand_id', $model->getFillable()) && empty($model->brand_id)) {
                $currentBrand = get_current_brand();
                if ($currentBrand) {
                    $model->brand_id = $currentBrand->id;
                }
            }
        });

        // Para relaciones n:m: si el modelo define el método "brands", lo usamos para asignar la relación
        static::created(function ($model) {
            if (method_exists($model, 'brands') && $model->brands()->count() === 0) {
                $currentBrand = get_current_brand();
                if ($currentBrand) {
                    $model->brands()->attach($currentBrand->id);
                }
            }
        });
    }
}
