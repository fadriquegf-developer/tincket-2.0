<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ÍNDICES PARA cache_session_slot
        Schema::table('cache_session_slot', function (Blueprint $table) {
            // Índice compuesto principal para búsquedas
            if (!$this->indexExists('cache_session_slot', 'idx_session_slot')) {
                $table->index(['session_id', 'slot_id'], 'idx_session_slot');
            }

            // Índice para agrupación por zonas
            if (!$this->indexExists('cache_session_slot', 'idx_session_zone')) {
                $table->index(['session_id', 'zone_id'], 'idx_session_zone');
            }

            // Índice para filtrar por estado de bloqueo
            if (!$this->indexExists('cache_session_slot', 'idx_session_locked')) {
                $table->index(['session_id', 'is_locked'], 'idx_session_locked');
            }
        });

        // 2. ÍNDICES PARA inscriptions
        Schema::table('inscriptions', function (Blueprint $table) {
            // Índice compuesto para búsquedas por sesión y tarifa
            if (!$this->indexExists('inscriptions', 'idx_session_rate_cart')) {
                $table->index(['session_id', 'rate_id', 'cart_id'], 'idx_session_rate_cart');
            }

            // Índice para búsquedas por slot
            if (!$this->indexExists('inscriptions', 'idx_slot_session_deleted')) {
                $table->index(['slot_id', 'session_id', 'deleted_at'], 'idx_slot_session_deleted');
            }

            // Índice para búsquedas por grupo de pack
            if (!$this->indexExists('inscriptions', 'idx_group_pack')) {
                $table->index(['group_pack_id', 'session_id'], 'idx_group_pack');
            }
        });

        // 3. ÍNDICES PARA session_slot
        Schema::table('session_slot', function (Blueprint $table) {
            // Índice compuesto para búsquedas por sesión y estado
            if (!$this->indexExists('session_slot', 'idx_session_status')) {
                $table->index(['session_id', 'status_id'], 'idx_session_status');
            }

            // Índice para búsquedas por slot
            if (!$this->indexExists('session_slot', 'idx_slot_session')) {
                $table->index(['slot_id', 'session_id'], 'idx_slot_session');
            }
        });

        // 4. ÍNDICES PARA session_temp_slot
        Schema::table('session_temp_slot', function (Blueprint $table) {
            // Índice para búsquedas de slots no expirados
            if (!$this->indexExists('session_temp_slot', 'idx_session_expires')) {
                $table->index(['session_id', 'expires_on', 'deleted_at'], 'idx_session_expires');
            }

            // Índice para búsquedas por cart
            if (!$this->indexExists('session_temp_slot', 'idx_cart_expires')) {
                $table->index(['cart_id', 'expires_on'], 'idx_cart_expires');
            }
        });

        // 5. ÍNDICES PARA carts
        Schema::table('carts', function (Blueprint $table) {
            // Índice para carritos no expirados
            if (!$this->indexExists('carts', 'idx_expires_confirmation')) {
                $table->index(['expires_on', 'confirmation_code'], 'idx_expires_confirmation');
            }

            // Índice para búsquedas por brand
            if (!$this->indexExists('carts', 'idx_brand_created')) {
                $table->index(['brand_id', 'created_at'], 'idx_brand_created');
            }
        });

        // 6. ÍNDICES PARA assignated_rates
        Schema::table('assignated_rates', function (Blueprint $table) {
            // Índice compuesto para búsquedas por sesión
            if (!$this->indexExists('assignated_rates', 'idx_session_rate_type')) {
                $table->index(['session_id', 'rate_id', 'assignated_rate_type'], 'idx_session_rate_type');
            }

            // Índice para búsquedas públicas
            if (!$this->indexExists('assignated_rates', 'idx_session_public')) {
                $table->index(['session_id', 'is_public'], 'idx_session_public');
            }
        });

        // 7. ÍNDICES PARA slots
        Schema::table('slots', function (Blueprint $table) {
            // Índice para búsquedas por espacio y posición
            if (!$this->indexExists('slots', 'idx_space_position')) {
                $table->index(['space_id', 'x', 'y'], 'idx_space_position');
            }

            // Índice para búsquedas por zona
            if (!$this->indexExists('slots', 'idx_zone_space')) {
                $table->index(['zone_id', 'space_id'], 'idx_zone_space');
            }
        });

        // Mensaje de confirmación
        echo "✅ Índices de performance agregados correctamente.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de cache_session_slot
        Schema::table('cache_session_slot', function (Blueprint $table) {
            $table->dropIndex('idx_session_slot');
            $table->dropIndex('idx_session_zone');
            $table->dropIndex('idx_session_locked');
        });

        // Eliminar índices de inscriptions
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_session_rate_cart');
            $table->dropIndex('idx_slot_session_deleted');
            $table->dropIndex('idx_group_pack');
        });

        // Eliminar índices de session_slot
        Schema::table('session_slot', function (Blueprint $table) {
            $table->dropIndex('idx_session_status');
            $table->dropIndex('idx_slot_session');
        });

        // Eliminar índices de session_temp_slot
        Schema::table('session_temp_slot', function (Blueprint $table) {
            $table->dropIndex('idx_session_expires');
            $table->dropIndex('idx_cart_expires');
        });

        // Eliminar índices de carts
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('idx_expires_confirmation');
            $table->dropIndex('idx_brand_created');
        });

        // Eliminar índices de assignated_rates
        Schema::table('assignated_rates', function (Blueprint $table) {
            $table->dropIndex('idx_session_rate_type');
            $table->dropIndex('idx_session_public');
        });

        // Eliminar índices de slots
        Schema::table('slots', function (Blueprint $table) {
            $table->dropIndex('idx_space_position');
            $table->dropIndex('idx_zone_space');
        });
    }

    /**
     * Verifica si un índice existe en una tabla
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
