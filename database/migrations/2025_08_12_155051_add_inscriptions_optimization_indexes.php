<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===== payments =====
        Schema::table('payments', function (Blueprint $table) {
            $this->createIndexIfMissing('payments', 'idx_paid_at', ['paid_at']);
            $this->createIndexIfMissing('payments', 'idx_cart_paid', ['cart_id', 'paid_at']);
            $this->createIndexIfMissing('payments', 'idx_cart_paid_gateway', ['cart_id', 'paid_at', 'gateway']);
        });

        // ===== carts =====
        Schema::table('carts', function (Blueprint $table) {
            $this->createIndexIfMissing('carts', 'idx_brand_confirmation', ['brand_id', 'confirmation_code']);
            $this->createIndexIfMissing('carts', 'idx_seller', ['seller_type', 'seller_id']);
            $this->createIndexIfMissing('carts', 'idx_brand_seller_confirmation', ['brand_id', 'seller_type', 'seller_id', 'confirmation_code']);
            $this->createIndexIfMissing('carts', 'idx_carts_created_at', ['created_at']);
        });

        // ===== inscriptions =====
        Schema::table('inscriptions', function (Blueprint $table) {
            $this->createIndexIfMissing('inscriptions', 'idx_cart_session', ['cart_id', 'session_id']);
            $this->createIndexIfMissing('inscriptions', 'idx_session_price', ['session_id', 'price_sold']);
            $this->createIndexIfMissing('inscriptions', 'idx_cart_session_price', ['cart_id', 'session_id', 'price_sold']);
            $this->createIndexIfMissing('inscriptions', 'idx_inscriptions_session_id', ['session_id']);
            $this->createIndexIfMissing('inscriptions', 'idx_inscriptions_cart_id', ['cart_id']);
            $this->createIndexIfMissing('inscriptions', 'idx_inscriptions_updated_id', ['updated_at', 'id']);
        });

        // ===== sessions =====
        Schema::table('sessions', function (Blueprint $table) {
            $this->createIndexIfMissing('sessions', 'idx_brand_event', ['brand_id', 'event_id']);
            $this->createIndexIfMissing('sessions', 'idx_event', ['event_id']);
            $this->createIndexIfMissing('sessions', 'idx_sessions_brand_id', ['brand_id']);
            $this->createIndexIfMissing('sessions', 'idx_sessions_event_id', ['event_id']);
        });

        // ===== events/sessions/slots: índices por nombre con prefijo =====
        $this->addPrefixIndexIfMissing('sessions', 'name', 'idx_sessions_name', 191);
        $this->addPrefixIndexIfMissing('events', 'name', 'idx_events_name', 191);
        if (Schema::hasTable('slots')) {
            $this->addPrefixIndexIfMissing('slots', 'name', 'idx_slots_name', 191);
        }

        // ===== partnerships (si existe) =====
        if (Schema::hasTable('partnerships')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $this->createIndexIfMissing('partnerships', 'idx_session_brand', ['session_id', 'brand_id']);
            });
        }

        // ===== Columnas generadas/índices desde JSON (si el motor lo soporta) =====
        if ($this->supportsJsonIndexes()) {
            $stored = $this->isMariaDb() ? 'PERSISTENT' : 'STORED';

            if (!Schema::hasColumn('payments', 'gateway_amount')) {
                DB::statement("
                    ALTER TABLE payments
                    ADD COLUMN gateway_amount DECIMAL(10,2) AS (
                        CAST(JSON_EXTRACT(gateway_response, \"$.Ds_Amount\") AS DECIMAL(10,2)) / 100
                    ) {$stored}
                ");
            }
            $this->createRawIndexIfMissing('payments', 'idx_gateway_amount', 'gateway_amount');

            if (!Schema::hasColumn('payments', 'gateway_payment_type')) {
                DB::statement("
                    ALTER TABLE payments
                    ADD COLUMN gateway_payment_type VARCHAR(50) AS (
                        JSON_UNQUOTE(JSON_EXTRACT(gateway_response, \"$.payment_type\"))
                    ) {$stored}
                ");
            }
            $this->createRawIndexIfMissing('payments', 'idx_gateway_payment_type', 'gateway_payment_type');
        }

        // ===== ANALYZE =====
        $this->analyzeTables(['payments', 'carts', 'inscriptions', 'sessions', 'events', 'brands', 'slots', 'partnerships']);
    }

    public function down(): void
    {
        // payments
        Schema::table('payments', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'payments', 'idx_paid_at');
            $this->dropIndexIfExists($table, 'payments', 'idx_cart_paid');
            $this->dropIndexIfExists($table, 'payments', 'idx_cart_paid_gateway');
            $this->dropIndexIfExists($table, 'payments', 'idx_gateway_amount');
            $this->dropIndexIfExists($table, 'payments', 'idx_gateway_payment_type');
        });
        if ($this->supportsJsonIndexes()) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'gateway_amount'))
                    $table->dropColumn('gateway_amount');
                if (Schema::hasColumn('payments', 'gateway_payment_type'))
                    $table->dropColumn('gateway_payment_type');
            });
        }

        // carts
        Schema::table('carts', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'carts', 'idx_brand_confirmation');
            $this->dropIndexIfExists($table, 'carts', 'idx_seller');
            $this->dropIndexIfExists($table, 'carts', 'idx_brand_seller_confirmation');
            $this->dropIndexIfExists($table, 'carts', 'idx_carts_created_at');
        });

        // inscriptions
        Schema::table('inscriptions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_cart_session');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_session_price');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_cart_session_price');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_inscriptions_session_id');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_inscriptions_cart_id');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_inscriptions_updated_id');
        });

        // sessions
        Schema::table('sessions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'sessions', 'idx_brand_event');
            $this->dropIndexIfExists($table, 'sessions', 'idx_event');
            $this->dropIndexIfExists($table, 'sessions', 'idx_sessions_brand_id');
            $this->dropIndexIfExists($table, 'sessions', 'idx_sessions_event_id');
            $this->dropIndexIfExists($table, 'sessions', 'idx_sessions_name');
        });

        // events
        Schema::table('events', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'events', 'idx_events_name');
        });

        // slots
        if (Schema::hasTable('slots')) {
            Schema::table('slots', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'slots', 'idx_slots_name');
            });
        }

        // partnerships
        if (Schema::hasTable('partnerships')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'partnerships', 'idx_session_brand');
            });
        }
    }

    /* ===================== Helpers ===================== */

    private function realTable(string $table): string
    {
        // Devuelve el nombre real de la tabla con el prefijo configurado (si lo hay)
        return DB::getTablePrefix() . $table;
    }

    private function isMySqlFamily(): bool
    {
        try {
            return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isMariaDb(): bool
    {
        if (!$this->isMySqlFamily())
            return false;
        try {
            $v = DB::selectOne("SELECT VERSION() AS v")->v ?? '';
            return stripos($v, 'mariadb') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function supportsJsonIndexes(): bool
    {
        if (!$this->isMySqlFamily())
            return false;
        try {
            $v = DB::selectOne("SELECT VERSION() AS v")->v ?? '';
            if ($this->isMariaDb()) {
                if (preg_match('/(\d+)\.(\d+)\./', $v, $m)) {
                    return ((int) $m[1] > 10) || ((int) $m[1] === 10 && (int) $m[2] >= 2);
                }
                return false;
            }
            // MySQL
            if (preg_match('/(\d+)\.(\d+)\./', $v, $m)) {
                return ((int) $m[1] > 5) || ((int) $m[1] === 5 && (int) $m[2] >= 7);
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $real = $this->realTable($table);
            $row = DB::selectOne(
                "SELECT 1
               FROM information_schema.statistics
              WHERE table_schema = DATABASE()
                AND table_name   = ?
                AND index_name   = ?
              LIMIT 1",
                [$real, $indexName]
            );
            return (bool) $row;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function createIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (!$this->isMySqlFamily() || !Schema::hasTable($table))
            return;
        if ($this->indexExists($table, $indexName))
            return;

        // El schema builder aplica el prefijo automáticamente
        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    private function createRawIndexIfMissing(string $table, string $indexName, string $columnExpr): void
    {
        if (!$this->isMySqlFamily() || !Schema::hasTable($table))
            return;
        if ($this->indexExists($table, $indexName))
            return;

        $real = $this->realTable($table);
        DB::statement("ALTER TABLE `{$real}` ADD INDEX `{$indexName}` ({$columnExpr})");
    }

    private function addPrefixIndexIfMissing(string $table, string $column, string $indexName, int $length = 191): void
    {
        if (!$this->isMySqlFamily() || !Schema::hasTable($table))
            return;
        if (!Schema::hasColumn($table, $column))
            return;
        if ($this->indexExists($table, $indexName))
            return;

        $real = $this->realTable($table);
        // Prefijo para TEXT/VARCHAR (evita error 1170 en MySQL)
        DB::statement("ALTER TABLE `{$real}` ADD INDEX `{$indexName}` (`{$column}`({$length}))");
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        // Laravel aplicará el prefijo internamente al dropear
        if ($this->indexExists($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }

    private function analyzeTables(array $tables): void
    {
        if (!$this->isMySqlFamily())
            return;
        foreach ($tables as $t) {
            if (!Schema::hasTable($t))
                continue;
            try {
                $real = $this->realTable($t);
                DB::statement("ANALYZE TABLE `{$real}`");
            } catch (\Throwable $e) {
                // ignorar si no hay permisos/soporte
            }
        }
    }
};
