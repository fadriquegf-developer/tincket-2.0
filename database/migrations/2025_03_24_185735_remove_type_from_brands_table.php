<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->char('type', 1)->nullable();
        });
    }
};
