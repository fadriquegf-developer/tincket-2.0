<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migración para resolver la confusión entre weight y order
 * 
 * Archivo: 2025_09_30_180000_fix_weight_order_confusion.php
 */
class FixWeightOrderConfusion extends Migration
{
    public function up()
    {
        Log::info('=== INICIANDO FIX WEIGHT VS ORDER ===');

        // Actualizar weights basados en el orden actual en formularios
        $this->syncWeightWithOrder();

        // Agregar índice para mejorar performance del ordenamiento
        if (!$this->indexExists('form_fields', 'idx_weight')) {
            DB::statement('CREATE INDEX idx_weight ON form_fields(weight)');
        }

        Log::info('=== FIX WEIGHT VS ORDER COMPLETADO ===');
    }

    public function down()
    {
        // Opcionalmente eliminar el índice
        if ($this->indexExists('form_fields', 'idx_weight')) {
            DB::statement('DROP INDEX idx_weight ON form_fields');
        }
    }

    private function syncWeightWithOrder()
    {
        // Obtener el promedio de order para cada campo
        $avgOrders = DB::select("
            SELECT 
                form_field_id,
                AVG(`order`) as avg_order,
                COUNT(*) as usage_count
            FROM form_form_field
            GROUP BY form_field_id
            ORDER BY avg_order
        ");

        // Actualizar weight basado en el promedio de order
        $weight = 1;
        foreach ($avgOrders as $field) {
            DB::table('form_fields')
                ->where('id', $field->form_field_id)
                ->update([
                    'weight' => $weight * 10, // Multiplicar por 10 para dejar espacio
                    'updated_at' => now()
                ]);

            $weight++;
        }

        // Campos no usados en ningún formulario, ponerlos al final
        $unusedFields = DB::table('form_fields')
            ->whereNotIn('id', collect($avgOrders)->pluck('form_field_id'))
            ->orderBy('weight')
            ->pluck('id');

        foreach ($unusedFields as $fieldId) {
            DB::table('form_fields')
                ->where('id', $fieldId)
                ->update([
                    'weight' => $weight * 10,
                    'updated_at' => now()
                ]);
            $weight++;
        }

        Log::info("Actualizados weights para " . (count($avgOrders) + count($unusedFields)) . " campos");
    }

    private function indexExists($table, $indexName)
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
}
