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
        // Primero agregar índices optimizados a las tablas que consultaremos
        Schema::table('inscriptions', function (Blueprint $table) {
            if (!Schema::hasIndex('inscriptions', 'idx_slot_session')) {
                $table->index(['slot_id', 'session_id', 'deleted_at'], 'idx_slot_session');
            }
            if (!Schema::hasIndex('inscriptions', 'idx_session_rate_cart')) {
                $table->index(['session_id', 'rate_id', 'cart_id'], 'idx_session_rate_cart');
            }
        });

        Schema::table('session_slot', function (Blueprint $table) {
            if (!Schema::hasIndex('session_slot', 'idx_session_slot')) {
                $table->index(['session_id', 'slot_id'], 'idx_session_slot');
            }
            if (!Schema::hasIndex('session_slot', 'idx_slot_status')) {
                $table->index(['slot_id', 'status_id'], 'idx_slot_status');
            }
        });

        Schema::table('session_temp_slot', function (Blueprint $table) {
            if (!Schema::hasIndex('session_temp_slot', 'idx_session_expires')) {
                $table->index(['session_id', 'expires_on'], 'idx_session_expires');
            }
            if (!Schema::hasIndex('session_temp_slot', 'idx_slot_expires')) {
                $table->index(['slot_id', 'expires_on'], 'idx_slot_expires');
            }
        });

        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasIndex('carts', 'idx_expires_confirmation')) {
                $table->index(['expires_on', 'confirmation_code'], 'idx_expires_confirmation');
            }
        });

        Schema::table('slots', function (Blueprint $table) {
            if (!Schema::hasIndex('slots', 'idx_space_zone')) {
                $table->index(['space_id', 'zone_id'], 'idx_space_zone');
            }
        });

        // Eliminar la tabla cache_session_slot
        Schema::dropIfExists('cache_session_slot');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear la tabla cache_session_slot si se hace rollback
        Schema::create('cache_session_slot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->integer('lock_reason')->nullable();
            $table->text('comment')->nullable();
            $table->json('rates_info')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'slot_id']);
            $table->index('session_id');
            $table->index('slot_id');
            $table->index('zone_id');
            $table->index('cart_id');
            $table->index(['session_id', 'updated_at']);
        });

        // Eliminar índices agregados
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_slot_session');
            $table->dropIndex('idx_session_rate_cart');
        });

        Schema::table('session_slot', function (Blueprint $table) {
            $table->dropIndex('idx_session_slot');
            $table->dropIndex('idx_slot_status');
        });

        Schema::table('session_temp_slot', function (Blueprint $table) {
            $table->dropIndex('idx_session_expires');
            $table->dropIndex('idx_slot_expires');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_expires_confirmation');
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->dropIndex('idx_space_zone');
        });
    }
};
