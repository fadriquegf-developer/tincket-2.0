<?php

namespace App\Models;

use App\Models\Brand;
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
        // NO aplicamos BrandScope global aquí
        // El filtrado se hace en el controlador según el contexto
    }

    /**
     * Obtiene una etiqueta que indica si el rol es general o de brand específica
     */
    public function getScopeLabel(): string
    {
        if (is_null($this->brand_id)) {
            return 'General';
        }

        // Si tiene brand_id, mostrar el nombre de la brand
        return $this->brand ? $this->brand->name : 'Brand #' . $this->brand_id;
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

    /**
     * Obtiene el nombre mostrable del rol con traducción si aplica
     */
    public function getDisplayNameAttribute()
    {
        if (is_null($this->brand_id)) {
            $translations = [
                'es' => [
                    'admin'    => 'Administrador',
                    'manager'  => 'Gestor',
                    'employee' => 'Empleado',
                    'seller'   => 'Vendedor',
                ],
                'ca' => [
                    'admin'    => 'Administrador',
                    'manager'  => 'Gestor',
                    'employee' => 'Empleat',
                    'seller'   => 'Venedor',
                ],
                'gl' => [
                    'admin'    => 'Administrador',
                    'manager'  => 'Xestor',
                    'employee' => 'Empregado',
                    'seller'   => 'Vendedor',
                ],
            ];

            $locale = app()->getLocale();
            if (isset($translations[$locale][$this->name])) {
                return $translations[$locale][$this->name];
            }
        }

        return $this->name; // fallback
    }

    /**
     * Scope para filtrar roles por brand incluyendo roles generales
     */
    public function scopeForCurrentBrand($query)
    {
        $brandId = get_current_brand_id();

        if (!$brandId) {
            // Si no hay brand, solo roles generales
            return $query->whereNull('brand_id');
        }

        return $query->where(function ($q) use ($brandId) {
            $q->where('brand_id', $brandId)
                ->orWhereNull('brand_id');
        });
    }
}
