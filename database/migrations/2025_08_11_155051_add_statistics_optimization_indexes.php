<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // payments
        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'idx_paid_at') === false) {
                $table->index('paid_at', 'idx_paid_at');
            }
            if ($this->indexExists('payments', 'idx_cart_paid') === false) {
                $table->index(['cart_id', 'paid_at'], 'idx_cart_paid');
            }
            if ($this->indexExists('payments', 'idx_cart_paid_gateway') === false) {
                $table->index(['cart_id', 'paid_at', 'gateway'], 'idx_cart_paid_gateway');
            }
        });

        // carts
        Schema::table('carts', function (Blueprint $table) {
            if ($this->indexExists('carts', 'idx_brand_confirmation') === false) {
                $table->index(['brand_id', 'confirmation_code'], 'idx_brand_confirmation');
            }
            if ($this->indexExists('carts', 'idx_seller') === false) {
                $table->index(['seller_type', 'seller_id'], 'idx_seller');
            }
            if ($this->indexExists('carts', 'idx_brand_seller_confirmation') === false) {
                $table->index(['brand_id', 'seller_type', 'seller_id', 'confirmation_code'], 'idx_brand_seller_confirmation');
            }
        });

        // inscriptions
        Schema::table('inscriptions', function (Blueprint $table) {
            if ($this->indexExists('inscriptions', 'idx_cart_session') === false) {
                $table->index(['cart_id', 'session_id'], 'idx_cart_session');
            }
            if ($this->indexExists('inscriptions', 'idx_session_price') === false) {
                $table->index(['session_id', 'price_sold'], 'idx_session_price');
            }
            if ($this->indexExists('inscriptions', 'idx_cart_session_price') === false) {
                $table->index(['cart_id', 'session_id', 'price_sold'], 'idx_cart_session_price');
            }
        });

        // sessions
        Schema::table('sessions', function (Blueprint $table) {
            if ($this->indexExists('sessions', 'idx_brand_event') === false) {
                $table->index(['brand_id', 'event_id'], 'idx_brand_event');
            }
            if ($this->indexExists('sessions', 'idx_event') === false) {
                $table->index('event_id', 'idx_event');
            }
        });

        // partnerships (si existe)
        if (Schema::hasTable('partnerships')) {
            Schema::table('partnerships', function (Blueprint $table) {
                if ($this->indexExists('partnerships', 'idx_session_brand') === false) {
                    $table->index(['session_id', 'brand_id'], 'idx_session_brand');
                }
            });
        }

        // Índices/columnas generadas desde JSON (solo MySQL/MariaDB)
        if ($this->supportsJsonIndexes()) {
            $isMaria = $this->isMariaDb();
            $storedKeyword = $isMaria ? 'PERSISTENT' : 'STORED';

            // gateway_amount
            DB::statement("
                ALTER TABLE payments
                ADD COLUMN gateway_amount DECIMAL(10,2) AS (
                    CAST(JSON_EXTRACT(gateway_response, \"$.Ds_Amount\") AS DECIMAL(10,2)) / 100
                ) {$storedKeyword},
                ADD INDEX idx_gateway_amount (gateway_amount)
            ");

            // gateway_payment_type
            DB::statement("
                ALTER TABLE payments
                ADD COLUMN gateway_payment_type VARCHAR(50) AS (
                    JSON_UNQUOTE(JSON_EXTRACT(gateway_response, \"$.payment_type\"))
                ) {$storedKeyword},
                ADD INDEX idx_gateway_payment_type (gateway_payment_type)
            ");
        }

        // ANALYZE (solo MySQL/MariaDB)
        $this->analyzeTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // payments
        Schema::table('payments', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'payments', 'idx_paid_at');
            $this->dropIndexIfExists($table, 'payments', 'idx_cart_paid');
            $this->dropIndexIfExists($table, 'payments', 'idx_cart_paid_gateway');
        });

        // carts
        Schema::table('carts', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'carts', 'idx_brand_confirmation');
            $this->dropIndexIfExists($table, 'carts', 'idx_seller');
            $this->dropIndexIfExists($table, 'carts', 'idx_brand_seller_confirmation');
        });

        // inscriptions
        Schema::table('inscriptions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_cart_session');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_session_price');
            $this->dropIndexIfExists($table, 'inscriptions', 'idx_cart_session_price');
        });

        // sessions
        Schema::table('sessions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'sessions', 'idx_brand_event');
            $this->dropIndexIfExists($table, 'sessions', 'idx_event');
        });

        // partnerships
        if (Schema::hasTable('partnerships')) {
            Schema::table('partnerships', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'partnerships', 'idx_session_brand');
            });
        }

        // columnas generadas JSON
        if ($this->supportsJsonIndexes()) {
            // Mejor eliminar índices antes por si el motor no los borra al dropear columna
            Schema::table('payments', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'payments', 'idx_gateway_amount');
                $this->dropIndexIfExists($table, 'payments', 'idx_gateway_payment_type');
            });

            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'gateway_amount')) {
                    $table->dropColumn('gateway_amount');
                }
                if (Schema::hasColumn('payments', 'gateway_payment_type')) {
                    $table->dropColumn('gateway_payment_type');
                }
            });
        }
    }

    /**
     * Comprueba si existe un índice (solo MySQL/MariaDB; en otros drivers devuelve false).
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            if (!$this->isMySqlFamily() || !Schema::hasTable($table)) {
                return false;
            }
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($rows) > 0;
        } catch (\Throwable $e) {
            // En SQLite u otros drivers fallará el SHOW INDEX: lo tratamos como "no existe"
            return false;
        }
    }

    /**
     * Elimina un índice si existe (seguro en cualquier driver).
     */
    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        try {
            if ($this->indexExists($tableName, $indexName)) {
                $table->dropIndex($indexName);
            }
        } catch (\Throwable $e) {
            // ignorar en drivers no compatibles
        }
    }

    /**
     * ¿Driver MySQL/MariaDB?
     */
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
        if (!$this->isMySqlFamily()) {
            return false;
        }
        try {
            $v = DB::select("SELECT VERSION() AS v")[0]->v ?? '';
            return stripos($v, 'mariadb') !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ¿Soporta columnas generadas/índices sobre JSON?
     * MySQL 5.7+ y MariaDB 10.2+ (aprox). Si no podemos detectar, devolvemos false.
     */
    private function supportsJsonIndexes(): bool
    {
        if (!$this->isMySqlFamily()) {
            return false;
        }
        try {
            $v = DB::select("SELECT VERSION() AS v")[0]->v ?? '';
            if ($this->isMariaDb()) {
                if (preg_match('/(\d+)\.(\d+)\./', $v, $m)) {
                    return ((int)$m[1] > 10) || ((int)$m[1] === 10 && (int)$m[2] >= 2);
                }
                return false;
            }
            // MySQL
            if (preg_match('/(\d+)\.(\d+)\./', $v, $m)) {
                return ((int)$m[1] > 5) || ((int)$m[1] === 5 && (int)$m[2] >= 7);
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ANALYZE TABLE para refrescar estadísticas del optimizador (solo MySQL/MariaDB).
     */
    private function analyzeTables(): void
    {
        if (!$this->isMySqlFamily()) {
            return;
        }
        $tables = ['payments', 'carts', 'inscriptions', 'sessions', 'events', 'brands'];

        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                try {
                    DB::statement("ANALYZE TABLE `{$t}`");
                } catch (\Throwable $e) {
                    // ignorar si el motor/permiso no lo permite
                }
            }
        }
    }
};
