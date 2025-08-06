<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1) Quitar la FK y la columna de sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['space_configuration_id']);
            $table->dropColumn('space_configuration_id');
        });

        // 2) Eliminar la tabla de detalles pivot
        Schema::dropIfExists('space_configuration_details');

        // 3) Eliminar la tabla de configuraciones
        Schema::dropIfExists('space_configurations');
    }

    public function down()
    {
        // 1) Recrear space_configurations
        Schema::create('space_configurations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('space_id');
            $table->string('name');
            $table->text('description')->nullable();
            // No timestamps según tu esquema original
            $table->foreign('space_id')
                  ->references('id')
                  ->on('spaces')
                  ->onDelete('cascade');
        });

        // 2) Recrear space_configuration_details
        Schema::create('space_configuration_details', function (Blueprint $table) {
            $table->unsignedInteger('space_configuration_id');
            $table->unsignedInteger('zone_id');
            $table->unsignedBigInteger('slot_id');

            $table->foreign('space_configuration_id')
                  ->references('id')
                  ->on('space_configurations')
                  ->onDelete('cascade');
            $table->foreign('zone_id')
                  ->references('id')
                  ->on('zones')
                  ->onDelete('cascade');
            $table->foreign('slot_id')
                  ->references('id')
                  ->on('slots')
                  ->onDelete('cascade');
        });

        // 3) Volver a añadir la columna y FK en sessions
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedInteger('space_configuration_id')
                  ->nullable()
                  ->after('space_id');
            $table->foreign('space_configuration_id')
                  ->references('id')
                  ->on('space_configurations')
                  ->onDelete('set null');
        });
    }
};
