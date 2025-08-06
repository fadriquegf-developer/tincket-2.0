<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Añade un índice a deleted_at para acelerar las consultas onlyTrashed
            $table->index('deleted_at', 'idx_carts_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_carts_deleted_at');
        });
    }
};

