<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mapeo de clases antiguas a nuevas
        $classMap = [
            'App\\Event' => 'App\\Models\\Event',
            'App\\Page' => 'App\\Models\\Page',
            'App\\Post' => 'App\\Models\\Post',
            'App\\Taxonomy' => 'App\\Models\\Taxonomy',
            'App\\MenuItem' => 'App\\Models\\MenuItem',
        ];

        // Obtener todos los mailings que tienen extra_content
        $mailings = DB::table('mailings')
            ->whereNotNull('extra_content')
            ->where('extra_content', '!=', '')
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($mailings as $mailing) {
            // Decodificar el JSON
            $extraContent = json_decode($mailing->extra_content, true);

            // Si no es un array válido, saltar
            if (!is_array($extraContent)) {
                $skipped++;
                continue;
            }

            // Si no tiene embedded_entities, saltar
            if (!isset($extraContent['embedded_entities']) || !is_array($extraContent['embedded_entities'])) {
                $skipped++;
                continue;
            }

            $needsUpdate = false;

            // Recorrer cada embedded_entity y actualizar el namespace
            foreach ($extraContent['embedded_entities'] as &$entity) {
                if (isset($entity['embeded_type']) && isset($classMap[$entity['embeded_type']])) {
                    $entity['embeded_type'] = $classMap[$entity['embeded_type']];
                    $needsUpdate = true;
                }
            }

            // Si hubo cambios, actualizar el registro
            if ($needsUpdate) {
                DB::table('mailings')
                    ->where('id', $mailing->id)
                    ->update([
                        'extra_content' => json_encode($extraContent),
                        'updated_at' => now(),
                    ]);
                $updated++;
            } else {
                $skipped++;
            }
        }

        // Log del resultado
        \Log::info("Migración de namespaces en mailings completada", [
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $mailings->count(),
        ]);

        echo "✓ Migración completada: {$updated} mailings actualizados, {$skipped} sin cambios\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mapeo inverso (de nuevas a antiguas)
        $classMap = [
            'App\\Models\\Event' => 'App\\Event',
            'App\\Models\\Page' => 'App\\Page',
            'App\\Models\\Post' => 'App\\Post',
            'App\\Models\\Taxonomy' => 'App\\Taxonomy',
            'App\\Models\\MenuItem' => 'App\\MenuItem',
        ];

        // Obtener todos los mailings que tienen extra_content
        $mailings = DB::table('mailings')
            ->whereNotNull('extra_content')
            ->where('extra_content', '!=', '')
            ->get();

        $reverted = 0;

        foreach ($mailings as $mailing) {
            $extraContent = json_decode($mailing->extra_content, true);

            if (!is_array($extraContent) || !isset($extraContent['embedded_entities'])) {
                continue;
            }

            $needsUpdate = false;

            foreach ($extraContent['embedded_entities'] as &$entity) {
                if (isset($entity['embeded_type']) && isset($classMap[$entity['embeded_type']])) {
                    $entity['embeded_type'] = $classMap[$entity['embeded_type']];
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                DB::table('mailings')
                    ->where('id', $mailing->id)
                    ->update([
                        'extra_content' => json_encode($extraContent),
                        'updated_at' => now(),
                    ]);
                $reverted++;
            }
        }

        echo "✓ Rollback completado: {$reverted} mailings revertidos\n";
    }
};
