<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Brand;
use App\Models\Setting;
use App\Scopes\BrandScope;

class MigratePartnershipedBrandsToParentId extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {

            // Solo los settings con la clave que nos interesa
            Setting::withoutGlobalScopes()
                ->where('key', 'base.brand.partnershiped_ids')
                ->whereNotNull('value')
                ->chunkById(100, function ($settings) {
                    foreach ($settings as $setting) {

                        // ID de la marca “padre”
                        $parentId = (int) $setting->brand_id;

                        // Normalizamos la lista de hijos
                        $childIds = collect(explode(',', $setting->value))
                            ->map(fn($id) => (int) trim($id))
                            ->filter(fn($id) => $id && $id !== $parentId)
                            ->unique();

                        foreach ($childIds as $childId) {
                            /** @var \App\Models\Brand|null $child */
                            $child = Brand::withoutGlobalScopes()->find($childId);

                            // Saltamos si no existe o ya tiene otro padre
                            if (! $child || ($child->parent_id && $child->parent_id !== $parentId)) {
                                continue;
                            }

                            // Asignamos sin lanzar eventos
                            $child->parent_id = $parentId;
                            $child->saveQuietly();
                        }

                        // ✅ Opcional: elimina el setting para evitar datos “fantasma”
                        $setting->deleteQuietly();
                    }
                });
        });
    }

    public function down(): void
    {
       
    }
}
