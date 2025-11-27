<?php
// ============================================
// SetsBrandOnCreate.php - VERSIÓN COMPLETA SEGURA
// ============================================

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait SetsBrandOnCreate
{
    /**
     * Boot the trait
     */
    public static function bootSetsBrandOnCreate()
    {
        // Hook en el evento 'creating' para modelos con brand_id directo
        static::creating(function ($model) {
            if (!self::assignBrandIdBeforeCreate($model)) {
                throw new \RuntimeException(
                    'Cannot create ' . get_class($model) . ' without valid brand context'
                );
            }
        });

        // Hook en el evento 'created' para relaciones many-to-many
        static::created(function ($model) {
            self::attachBrandAfterCreate($model);
        });
    }

    /**
     * Asigna brand_id antes de crear el modelo (relaciones 1:n o 1:1)
     * VERSIÓN MEJORADA CON VALIDACIÓN ESTRICTA
     */
    protected static function assignBrandIdBeforeCreate($model): bool
    {
        $tableName = $model->getTable();

        // Verificar si la tabla tiene columna brand_id
        if (!Schema::hasColumn($tableName, 'brand_id')) {
            return true; // Se manejará en el hook 'created'
        }

        // Si ya tiene brand_id asignado, validar que existe
        if (!empty($model->brand_id) && $model->brand_id > 0) {
            // MEJORA: Validar que el brand_id existe y está activo
            $brandExists = DB::table('brands')
                ->where('id', $model->brand_id)
                ->whereNull('deleted_at')
                ->exists();

            if (!$brandExists) {
                Log::error("SetsBrandOnCreate: Invalid brand_id provided", [
                    'model' => get_class($model),
                    'brand_id' => $model->brand_id,
                    'user_id' => auth()->id()
                ]);
                return false;
            }

            return true;
        }

        // Obtener la brand actual
        $currentBrand = get_current_brand();

        // Verificar si brand_id es nullable en la base de datos
        $columns = DB::select("SHOW COLUMNS FROM {$tableName} WHERE Field = 'brand_id'");
        $brandIdColumn = $columns[0] ?? null;
        $isNullable = $brandIdColumn && $brandIdColumn->Null === 'YES';

        // Si no hay brand y el campo NO permite NULL
        if (!$currentBrand && !$isNullable) {
            $modelClass = get_class($model);

            // MEJORA: Solo permitir para superadmin en contexto engine
            if (self::isSuperuserContext() && get_brand_capability() === 'engine') {
                Log::warning("SetsBrandOnCreate: Superuser creating {$modelClass} without brand context");
                return true;
            }

            Log::error("SetsBrandOnCreate: Cannot create {$modelClass} without brand context (brand_id is NOT NULL)");
            return false;
        }

        // Asignar brand_id si existe brand actual
        if ($currentBrand) {
            $model->brand_id = $currentBrand->id;
        }

        return true;
    }

    /**
     * Adjunta brand después de crear el modelo (relaciones n:m)
     * VERSIÓN MEJORADA CON TRANSACCIONES Y VALIDACIÓN
     */
    protected static function attachBrandAfterCreate($model): void
    {
        // Verificar si el modelo tiene relación 'brands' (many-to-many)
        if (!method_exists($model, 'brands')) {
            return;
        }

        $currentBrand = get_current_brand();

        // Si no hay brand actual
        if (!$currentBrand) {
            $modelClass = get_class($model);

            // Para engine/superadmin es opcional
            if (get_brand_capability() === 'engine' && self::isSuperuserContext()) {
                return;
            }

            // MEJORA: Para otros casos, es un error crítico
            Log::error("SetsBrandOnCreate: {$modelClass} created without brand attachment - attempting rollback");

            if (self::shouldDeleteOnBrandAttachFailure($model)) {
                try {
                    $model->forceDelete();
                } catch (\Exception $e) {
                    Log::critical("SetsBrandOnCreate: Failed to delete model after brand attachment failure", [
                        'error' => $e->getMessage()
                    ]);
                }
                throw new \RuntimeException("Model created without brand context - rolled back");
            }

            return;
        }

        // Usar transacción para asegurar integridad
        DB::beginTransaction();

        try {
            // Verificar si ya existe la relación
            $existingRelation = $model->brands()
                ->where('brands.id', $currentBrand->id)
                ->exists();

            if ($existingRelation) {
                DB::commit();
                return;
            }

            // Adjuntar la brand
            $model->brands()->attach($currentBrand->id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verificar que se adjuntó correctamente
            if (!$model->brands()->where('brands.id', $currentBrand->id)->exists()) {
                throw new \RuntimeException("Failed to verify brand attachment");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            $modelClass = get_class($model);
            Log::error("SetsBrandOnCreate: Failed to attach brand to {$modelClass}: " . $e->getMessage());

            // Decidir si eliminar el modelo o continuar sin brand
            if (self::shouldDeleteOnBrandAttachFailure($model)) {
                try {
                    $model->forceDelete();
                } catch (\Exception $deleteException) {
                    Log::error("SetsBrandOnCreate: Failed to delete model after brand attachment failure: " . $deleteException->getMessage());
                }

                throw new \RuntimeException(
                    "Failed to assign brand to {$modelClass}. Model has been rolled back. Error: " . $e->getMessage()
                );
            }

            Log::error("SetsBrandOnCreate: Model {$modelClass} id={$model->id} created but without brand attachment");
        }
    }

    /**
     * Determina si se debe eliminar el modelo si falla la asignación de brand
     */
    protected static function shouldDeleteOnBrandAttachFailure($model): bool
    {
        // Para engine/superadmin, no eliminar
        if (get_brand_capability() === 'engine' && self::isSuperuserContext()) {
            return false;
        }

        // Si el modelo tiene un método para determinar esto, usarlo
        if (method_exists($model, 'requiresBrandAttachment')) {
            return $model->requiresBrandAttachment();
        }

        // Por defecto, eliminar si no es engine
        // Esto asegura consistencia en el sistema multi-tenant
        return true;
    }

    /**
     * Verifica si el contexto actual es de superusuario
     */
    protected static function isSuperuserContext(): bool
    {
        $user = backpack_user() ?? auth()->user();

        if (!$user) {
            return false;
        }

        $superuserIds = config('superusers.ids', [1]);
        return in_array($user->id, $superuserIds, true);
    }

    /**
     * Scope para filtrar por brand actual
     * MANTIENE LA FUNCIONALIDAD ORIGINAL
     */
    public function scopeCurrentBrand($query)
    {
        $currentBrand = get_current_brand();

        if (!$currentBrand) {
            // Si no hay brand, no retornar nada (excepto para engine)
            if (get_brand_capability() !== 'engine') {
                return $query->whereRaw('1 = 0');
            }
            return $query;
        }

        // Si el modelo tiene brand_id directo
        if (Schema::hasColumn($this->getTable(), 'brand_id')) {
            return $query->where('brand_id', $currentBrand->id);
        }

        // Si tiene relación brands (many-to-many)
        if (method_exists($this, 'brands')) {
            return $query->whereHas('brands', function ($q) use ($currentBrand) {
                $q->where('brands.id', $currentBrand->id);
            });
        }

        return $query;
    }
}