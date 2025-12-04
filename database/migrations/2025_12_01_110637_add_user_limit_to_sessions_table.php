<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sessions', function (Blueprint $table) {
            // 1️⃣ Campo para ACTIVAR/DESACTIVAR la limitación global
            $table->boolean('limit_per_user')
                ->default(false)
                ->after('code_type')
                ->comment('¿Está activa la limitación por usuario a nivel sesión?');
            
            // 2️⃣ Campo para GUARDAR el límite de entradas configurado
            $table->unsignedInteger('max_per_user')
                ->nullable()
                ->after('limit_per_user')
                ->comment('Número máximo de entradas que puede comprar un usuario en esta sesión');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn([
                'limit_per_user',
                'max_per_user'
            ]);
        });
    }
};