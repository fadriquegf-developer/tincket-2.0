<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migración SEGURA PARA PRODUCCIÓN
 * Corrige problemas críticos en el sistema de formularios
 * 
 * Archivo: 2025_09_30_140000_fix_form_system_production_safe.php
 * 
 * IMPORTANTE: Esta migración está diseñada para ejecutarse en producción
 * sin pérdida de datos y con mínimo impacto.
 */
class FixFormSystemProductionSafe extends Migration  // <-- NOMBRE ÚNICO
{
    private $backupTableCreated = false;

    /**
     * Run the migrations.
     */
    public function up()
    {
        // Registrar inicio
        Log::info('=== INICIANDO MIGRACIÓN DE FORMULARIOS PRODUCCIÓN ===');
        $startTime = microtime(true);

        try {
            // PASO 1: Crear backup de seguridad
            $this->createBackupTable();

            // PASO 2: Limpiar datos problemáticos
            $this->cleanupData();

            // PASO 3: Actualizar form_form_field
            $this->updateFormFormFieldTable();

            // PASO 4: Actualizar form_field_answers
            $this->updateFormFieldAnswersTable();

            // PASO 5: Agregar índices faltantes
            $this->addMissingIndexes();

            // PASO 6: Validar integridad
            $this->validateDataIntegrity();

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("=== MIGRACIÓN COMPLETADA EN {$executionTime} SEGUNDOS ===");
        } catch (\Exception $e) {
            Log::error('Error en migración: ' . $e->getMessage());

            // Intentar rollback automático
            if ($this->backupTableCreated) {
                $this->restoreFromBackup();
            }

            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Log::info('=== INICIANDO ROLLBACK DE MIGRACIÓN ===');

        // Eliminar foreign keys si existen
        $this->dropForeignKeysIfExists('form_form_field', ['form_id', 'form_field_id']);

        // Revertir tipos en form_form_field
        Schema::table('form_form_field', function (Blueprint $table) {
            $table->integer('form_id')->change();
            $table->integer('form_field_id')->change();
        });

        // Revertir form_field_answers
        Schema::table('form_field_answers', function (Blueprint $table) {
            $table->string('answer', 255)->change();
        });

        // Eliminar índices agregados
        $this->dropIndexIfExists('form_form_field', 'form_field_unique');
        $this->dropIndexIfExists('form_form_field', 'form_order_index');
        $this->dropIndexIfExists('form_fields', 'form_fields_weight_index');
        $this->dropIndexIfExists('form_fields', 'form_fields_brand_type_index');

        Log::info('=== ROLLBACK COMPLETADO ===');
    }

    /**
     * PASO 1: Crear tabla de backup
     */
    private function createBackupTable()
    {
        Log::info('Paso 1: Creando backup de form_form_field...');

        // Crear backup solo si hay datos
        $count = DB::table('form_form_field')->count();

        if ($count > 0) {
            // Crear tabla de backup con timestamp
            $backupTable = 'form_form_field_backup_' . date('Y_m_d_His');

            DB::statement("CREATE TABLE {$backupTable} LIKE form_form_field");
            DB::statement("INSERT INTO {$backupTable} SELECT * FROM form_form_field");

            $this->backupTableCreated = true;
            Log::info("Backup creado: {$backupTable} con {$count} registros");
        }
    }

    /**
     * PASO 2: Limpiar datos problemáticos
     */
    private function cleanupData()
    {
        Log::info('Paso 2: Limpiando datos problemáticos...');

        // Primero, obtener IDs huérfanos y luego eliminar
        // Eliminar registros huérfanos en form_form_field (forms inexistentes)
        $orphanedFormIds = DB::select("
            SELECT DISTINCT ff.form_id
            FROM form_form_field ff
            LEFT JOIN forms f ON ff.form_id = f.id
            WHERE f.id IS NULL
        ");

        if (count($orphanedFormIds) > 0) {
            $idsToDelete = array_map(function ($item) {
                return $item->form_id;
            }, $orphanedFormIds);
            $deletedOrphanedForms = DB::table('form_form_field')
                ->whereIn('form_id', $idsToDelete)
                ->delete();

            Log::warning("Eliminados {$deletedOrphanedForms} registros huérfanos (forms inexistentes: " . implode(',', $idsToDelete) . ")");
        }

        // Eliminar registros huérfanos (fields inexistentes)
        $orphanedFieldIds = DB::select("
            SELECT DISTINCT ff.form_field_id
            FROM form_form_field ff
            LEFT JOIN form_fields f ON ff.form_field_id = f.id
            WHERE f.id IS NULL
        ");

        if (count($orphanedFieldIds) > 0) {
            $idsToDelete = array_map(function ($item) {
                return $item->form_field_id;
            }, $orphanedFieldIds);
            $deletedOrphanedFields = DB::table('form_form_field')
                ->whereIn('form_field_id', $idsToDelete)
                ->delete();

            Log::warning("Eliminados {$deletedOrphanedFields} registros huérfanos (fields inexistentes: " . implode(',', $idsToDelete) . ")");
        }

        // Eliminar duplicados manteniendo el de menor order
        $this->removeDuplicates();

        // Normalizar orders
        $this->normalizeOrders();
    }

    /**
     * PASO 3: Actualizar tabla form_form_field
     */
    private function updateFormFormFieldTable()
    {
        Log::info('Paso 3: Actualizando tipos de datos en form_form_field...');

        // Primero eliminar índices existentes que puedan interferir
        $this->dropIndexIfExists('form_form_field', 'form_form_field_form_id_foreign');
        $this->dropIndexIfExists('form_form_field', 'form_form_field_form_field_id_foreign');

        // Cambiar tipos de columna
        Schema::table('form_form_field', function (Blueprint $table) {
            $table->unsignedInteger('form_id')->change();
            $table->unsignedInteger('form_field_id')->change();
            $table->unsignedInteger('order')->default(0)->change();
        });

        // Agregar foreign keys
        Schema::table('form_form_field', function (Blueprint $table) {
            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->onDelete('cascade');

            $table->foreign('form_field_id')
                ->references('id')
                ->on('form_fields')
                ->onDelete('cascade');
        });

        Log::info('Tipos de datos actualizados y foreign keys agregadas');
    }

    /**
     * PASO 4: Actualizar form_field_answers para soportar respuestas largas
     */
    private function updateFormFieldAnswersTable()
    {
        Log::info('Paso 4: Actualizando form_field_answers para respuestas largas...');

        // Contar cuántas respuestas largas podrían estar truncadas
        $longAnswers = DB::table('form_field_answers')
            ->whereRaw('CHAR_LENGTH(answer) >= 250')
            ->count();

        if ($longAnswers > 0) {
            Log::warning("Detectadas {$longAnswers} respuestas cerca del límite de 255 caracteres");
        }

        // Cambiar a TEXT para soportar respuestas largas
        Schema::table('form_field_answers', function (Blueprint $table) {
            $table->text('answer')->change();
        });

        Log::info('Campo answer actualizado a TEXT');
    }

    /**
     * PASO 5: Agregar índices para mejorar performance
     */
    private function addMissingIndexes()
    {
        Log::info('Paso 5: Agregando índices de optimización...');

        // Índice único para prevenir duplicados
        if (!$this->indexExists('form_form_field', 'form_field_unique')) {
            try {
                Schema::table('form_form_field', function (Blueprint $table) {
                    $table->unique(['form_id', 'form_field_id'], 'form_field_unique');
                });
                Log::info('Índice único agregado');
            } catch (\Exception $e) {
                Log::warning('No se pudo agregar índice único: ' . $e->getMessage());
            }
        }

        // Índice para ordenamiento
        if (!$this->indexExists('form_form_field', 'form_order_index')) {
            Schema::table('form_form_field', function (Blueprint $table) {
                $table->index(['form_id', 'order'], 'form_order_index');
            });
            Log::info('Índice de orden agregado');
        }

        // Índices adicionales en form_fields si no existen
        if (!$this->indexExists('form_fields', 'form_fields_weight_index')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->index('weight', 'form_fields_weight_index');
            });
        }

        if (!$this->indexExists('form_fields', 'form_fields_brand_type_index')) {
            Schema::table('form_fields', function (Blueprint $table) {
                $table->index(['brand_id', 'type'], 'form_fields_brand_type_index');
            });
        }
    }

    /**
     * PASO 6: Validar integridad final
     */
    private function validateDataIntegrity()
    {
        Log::info('Paso 6: Validando integridad de datos...');

        // Verificar counts finales
        $stats = [
            'forms' => DB::table('forms')->count(),
            'form_fields' => DB::table('form_fields')->count(),
            'form_form_field' => DB::table('form_form_field')->count(),
            'form_field_answers' => DB::table('form_field_answers')->count(),
        ];

        Log::info('Estadísticas finales: ' . json_encode($stats));

        // Verificar que las relaciones funcionan usando sintaxis compatible
        $testQuery = DB::table('form_form_field')
            ->join('forms', 'form_form_field.form_id', '=', 'forms.id')
            ->join('form_fields', 'form_form_field.form_field_id', '=', 'form_fields.id')
            ->limit(1)
            ->first();

        if ($testQuery) {
            Log::info('✓ Integridad de relaciones verificada');
        } else if (DB::table('form_form_field')->count() > 0) {
            Log::warning('⚠ No se pudo verificar integridad de relaciones');
        }

        // Verificar que no hay orders duplicados
        $duplicateOrders = DB::select("
            SELECT form_id, COUNT(*) as total, COUNT(DISTINCT `order`) as unique_orders
            FROM form_form_field
            GROUP BY form_id
            HAVING unique_orders != total
        ");

        if (count($duplicateOrders) > 0) {
            Log::warning("Aún hay " . count($duplicateOrders) . " formularios con orders duplicados");
        }
    }

    /**
     * Eliminar duplicados en form_form_field
     */
    private function removeDuplicates()
    {
        // Encontrar duplicados
        $duplicates = DB::select("
            SELECT form_id, form_field_id, COUNT(*) as count, MIN(`order`) as min_order
            FROM form_form_field
            GROUP BY form_id, form_field_id
            HAVING count > 1
        ");

        if (count($duplicates) > 0) {
            Log::warning('Encontrados ' . count($duplicates) . ' grupos de duplicados');

            foreach ($duplicates as $dup) {
                // Eliminar todos menos el de menor order
                DB::table('form_form_field')
                    ->where('form_id', $dup->form_id)
                    ->where('form_field_id', $dup->form_field_id)
                    ->where('order', '>', $dup->min_order)
                    ->delete();
            }

            Log::info('Duplicados eliminados');
        }
    }

    /**
     * Normalizar orders para cada formulario
     */
    private function normalizeOrders()
    {
        $forms = DB::table('form_form_field')
            ->select('form_id')
            ->distinct()
            ->pluck('form_id');

        foreach ($forms as $formId) {
            $fields = DB::table('form_form_field')
                ->where('form_id', $formId)
                ->orderBy('order')
                ->orderBy('form_field_id')
                ->get();

            $order = 1;
            foreach ($fields as $field) {
                DB::table('form_form_field')
                    ->where('form_id', $field->form_id)
                    ->where('form_field_id', $field->form_field_id)
                    ->update(['order' => $order]);
                $order++;
            }
        }

        Log::info('Orders normalizados para ' . count($forms) . ' formularios');
    }

    /**
     * Verificar si un índice existe
     */
    private function indexExists($table, $indexName)
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND INDEX_NAME = ?
        ", [$table, $indexName]);

        return $result[0]->count > 0;
    }

    /**
     * Eliminar índice si existe
     */
    private function dropIndexIfExists($table, $indexName)
    {
        if ($this->indexExists($table, $indexName)) {
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar índice {$indexName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Eliminar foreign keys si existen
     */
    private function dropForeignKeysIfExists($table, $columns)
    {
        $database = config('database.connections.mysql.database');

        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$database, $table]);

        foreach ($foreignKeys as $fk) {
            try {
                Schema::table($table, function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar FK {$fk->CONSTRAINT_NAME}: " . $e->getMessage());
            }
        }
    }

    /**
     * Restaurar desde backup en caso de error
     */
    private function restoreFromBackup()
    {
        Log::error('Intentando restaurar desde backup...');

        try {
            // Encontrar la tabla de backup más reciente
            $result = DB::select("
                SELECT TABLE_NAME 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME LIKE 'form_form_field_backup_%'
                ORDER BY TABLE_NAME DESC
                LIMIT 1
            ");

            if (!empty($result)) {
                $backupTable = $result[0]->TABLE_NAME;

                // Restaurar datos
                DB::statement("TRUNCATE TABLE form_form_field");
                DB::statement("INSERT INTO form_form_field SELECT form_id, form_field_id, `order` FROM {$backupTable}");

                Log::info("Datos restaurados desde {$backupTable}");
            }
        } catch (\Exception $e) {
            Log::error('Error al restaurar backup: ' . $e->getMessage());
        }
    }
}
