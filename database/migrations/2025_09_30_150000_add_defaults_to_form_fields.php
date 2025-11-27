<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agregar valores por defecto a campos requeridos en form_fields
 * VERSIÓN SIN TRIGGERS - Compatible con hosting restringido
 * 
 * Archivo: 2025_09_30_150000_add_defaults_to_form_fields.php
 */
class AddDefaultsToFormFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Primero, actualizar registros existentes con valores NULL
        DB::table('form_fields')
            ->whereNull('config')
            ->update(['config' => '{}']);

        DB::table('form_fields')
            ->whereNull('weight')
            ->update(['weight' => 0]);

        DB::table('form_fields')
            ->whereNull('is_editable')
            ->update(['is_editable' => 1]);

        // Solo modificar weight e is_editable con defaults
        // config NO puede tener default en MySQL strict con TEXT
        DB::statement("ALTER TABLE form_fields MODIFY weight SMALLINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE form_fields MODIFY is_editable TINYINT(1) DEFAULT 1");

        // Config permanece como LONGTEXT sin default
        // El modelo PHP manejará el valor por defecto
        DB::statement("ALTER TABLE form_fields MODIFY config LONGTEXT NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revertir los cambios
        DB::statement("ALTER TABLE form_fields MODIFY weight SMALLINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE form_fields MODIFY is_editable TINYINT(1) NULL");
        // Config permanece como LONGTEXT NOT NULL
    }
}
