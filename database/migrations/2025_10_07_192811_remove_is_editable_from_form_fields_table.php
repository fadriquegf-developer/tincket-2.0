<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // Eliminar la columna is_editable si existe
            if (Schema::hasColumn('form_fields', 'is_editable')) {
                $table->dropColumn('is_editable');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            // Restaurar la columna en caso de rollback
            $table->boolean('is_editable')->default(true)->after('weight');
        });
    }
};
