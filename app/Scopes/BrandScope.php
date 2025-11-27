<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BrandScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // No aplicar en consola (migraciones, seeders, comandos)
        if (app()->runningInConsole()) {
            return;
        }

        // Intentar obtener brand del contexto
        $brand = get_current_brand() ?? request()->get('brand');
        $brandId = $brand?->id;

        if (!$brandId) {
            return;  // ← Cambiar a solo return, no whereRaw('1 = 0')
        }

        $tableName = $model->getTable();

        // Verificar si la tabla tiene la columna brand_id directamente en la BD
        // Esto es más confiable que revisar fillable/guarded
        if (Schema::hasColumn($tableName, 'brand_id')) {
            $builder->where($tableName . '.brand_id', $brandId);
        }
        // Si el modelo tiene relación 'brands' (many-to-many)
        elseif (method_exists($model, 'brands')) {
            $builder->whereHas('brands', function ($query) use ($brandId) {
                $query->where('brands.id', $brandId);
            });
        }
        // Si el modelo tiene relación 'brand' (belongs-to)
        elseif (method_exists($model, 'brand')) {
            $builder->where($tableName . '.brand_id', $brandId);
        }
    }
}
