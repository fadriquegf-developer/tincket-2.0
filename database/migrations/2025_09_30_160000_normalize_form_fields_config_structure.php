<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migración para normalizar la estructura de config en form_fields
 * VERSIÓN SIN BACKUP
 * 
 * DE: {"options": {"values": [{"key": "...", "labels": {...}}], "expanded": true, "multiple": true}}
 * A:  {"options": [{"value": "...", "label": {...}}], "expanded": true, "multiple": true}
 * 
 * Archivo: 2025_09_30_160000_normalize_form_fields_config_structure.php
 */
class NormalizeFormFieldsConfigStructure extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Log::info('=== INICIANDO MIGRACIÓN DE NORMALIZACIÓN DE CONFIG ===');
        $startTime = microtime(true);

        // Obtener todos los form_fields con config
        $fields = DB::table('form_fields')
            ->whereNotNull('config')
            ->where('config', '!=', '{}')
            ->get();

        $totalFields = count($fields);
        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        Log::info("Procesando {$totalFields} campos con configuración...");

        foreach ($fields as $field) {
            try {
                $originalConfig = json_decode($field->config, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("Error decodificando JSON para field_id: {$field->id}");
                    $errorCount++;
                    continue;
                }

                // Transformar el config
                $newConfig = $this->transformConfig($originalConfig, $field->type);

                if ($newConfig !== $originalConfig) {
                    // Actualizar solo si hubo cambios
                    DB::table('form_fields')
                        ->where('id', $field->id)
                        ->update([
                            'config' => json_encode($newConfig, JSON_UNESCAPED_UNICODE),
                            'updated_at' => now()
                        ]);

                    $migratedCount++;

                    if ($migratedCount % 50 === 0) {
                        Log::info("Progreso: {$migratedCount} campos migrados...");
                    }
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                Log::error("Error procesando field_id {$field->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        Log::info("=== MIGRACIÓN COMPLETADA ===");
        Log::info("Total procesados: {$totalFields}");
        Log::info("Migrados: {$migratedCount}");
        Log::info("Sin cambios: {$skippedCount}");
        Log::info("Errores: {$errorCount}");
        Log::info("Tiempo: {$executionTime} segundos");

        if ($errorCount > 0) {
            Log::warning("⚠️ Hubo {$errorCount} errores. Revisa los logs para más detalles.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Log::warning('=== ROLLBACK NO DISPONIBLE - No se creó backup ===');
        Log::warning('Para revertir esta migración, necesitarías un backup manual de la base de datos');
    }

    /**
     * Transformar config del formato antiguo al nuevo
     */
    private function transformConfig($config, $fieldType)
    {
        // Si el config está vacío o no es array, retornar como está
        if (!is_array($config) || empty($config)) {
            return $config;
        }

        // Crear el nuevo config
        $newConfig = [];

        // Procesar options si existen
        if (isset($config['options'])) {
            // Detectar formato antiguo
            if (isset($config['options']['values']) && is_array($config['options']['values'])) {
                // FORMATO ANTIGUO - Transformar
                $newOptions = [];

                foreach ($config['options']['values'] as $option) {
                    $newOption = [];

                    // Mapear 'key' a 'value'
                    $newOption['value'] = $option['key'] ?? $option['value'] ?? '';

                    // Mapear 'labels' a 'label'
                    if (isset($option['labels'])) {
                        $newOption['label'] = $option['labels'];
                    } elseif (isset($option['label'])) {
                        $newOption['label'] = $option['label'];
                    } else {
                        // Si no hay label, usar el value como label
                        $newOption['label'] = $newOption['value'];
                    }

                    $newOptions[] = $newOption;
                }

                $newConfig['options'] = $newOptions;

                // Mover expanded y multiple al nivel raíz si existen
                if (isset($config['options']['expanded'])) {
                    $newConfig['expanded'] = $config['options']['expanded'];
                }
                if (isset($config['options']['multiple'])) {
                    $newConfig['multiple'] = $config['options']['multiple'];
                }
            } else {
                // Ya está en formato nuevo o es un formato diferente
                $newConfig['options'] = $config['options'];
            }
        }

        // Copiar otras propiedades que no sean 'options'
        foreach ($config as $key => $value) {
            if ($key !== 'options') {
                // Si la propiedad no se ha copiado ya
                if (!isset($newConfig[$key])) {
                    $newConfig[$key] = $value;
                }
            }
        }

        // Asegurar que 'required' existe basado en 'rules' si hay
        if (isset($newConfig['rules']) && !isset($newConfig['required'])) {
            $rules = is_array($newConfig['rules']) ? implode('|', $newConfig['rules']) : $newConfig['rules'];
            $newConfig['required'] = str_contains($rules, 'required');
        }

        // Para campos select, radio, checkbox, asegurar estructura correcta
        if (in_array($fieldType, ['select', 'radio', 'checkbox'])) {
            if (!isset($newConfig['options']) || !is_array($newConfig['options'])) {
                $newConfig['options'] = [];
            }

            // Validar que cada opción tenga value y label
            $newConfig['options'] = array_map(function ($option) {
                if (!is_array($option)) {
                    return ['value' => $option, 'label' => $option];
                }

                if (!isset($option['value'])) {
                    $option['value'] = $option['label'] ?? '';
                }
                if (!isset($option['label'])) {
                    $option['label'] = $option['value'] ?? '';
                }

                return $option;
            }, $newConfig['options']);
        }

        return $newConfig;
    }
}
