<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BrandScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!app()->runningInConsole()) {
            $brand = get_current_brand();
            $brandId = $brand?->id;
            if (!$brandId) {
                return;
            }

            // Si el modelo tiene el atributo brand_id, usamos un WHERE directo
            if (in_array('brand_id', $model->getFillable()) || $model->getTable() === 'brands') {
                $builder->where($model->getTable() . '.brand_id', $brandId);
            }
            // Si el modelo tiene definida la relación 'brands', se filtra con whereHas
            elseif (method_exists($model, 'brands')) {
                $builder->whereHas('brands', function($query) use ($brandId) {
                    $query->where('brands.id', $brandId);
                });
            }
        }
    }
}
