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
        // Verificar si la columna ya existe (por intentos anteriores)
        if (!Schema::hasColumn('inscriptions', 'brand_id')) {
            // Paso 1: Añadir columna brand_id como INTEGER (no BIGINT)
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->unsignedInteger('brand_id')->nullable()->after('cart_id');
            });

            // Paso 2: Poblar brand_id con los datos existentes del cart
            DB::statement('
                UPDATE inscriptions i
                INNER JOIN carts c ON i.cart_id = c.id
                SET i.brand_id = c.brand_id
                WHERE i.brand_id IS NULL AND c.brand_id IS NOT NULL
            ');

            // Paso 3: Verificar que no quedaron registros sin brand_id
            $nullBrandCount = DB::table('inscriptions')->whereNull('brand_id')->count();
            
            if ($nullBrandCount > 0) {
                $sampleRecords = DB::table('inscriptions')
                    ->select('inscriptions.id', 'inscriptions.cart_id', 'carts.brand_id as cart_brand_id')
                    ->leftJoin('carts', 'inscriptions.cart_id', '=', 'carts.id')
                    ->whereNull('inscriptions.brand_id')
                    ->limit(5)
                    ->get();
                
                throw new \Exception(
                    "Hay {$nullBrandCount} inscripciones sin brand_id. " .
                    "Registros de ejemplo: " . json_encode($sampleRecords)
                );
            }

            // Paso 4: Hacer la columna NOT NULL
            DB::statement('ALTER TABLE inscriptions MODIFY brand_id INT UNSIGNED NOT NULL');
        } else {
            // Si la columna ya existe, asegurarnos de que sea NOT NULL
            DB::statement('ALTER TABLE inscriptions MODIFY brand_id INT UNSIGNED NOT NULL');
        }

        // Paso 5: Añadir índices (verificando que no existan)
        Schema::table('inscriptions', function (Blueprint $table) {
            // Verificar y crear índice simple en brand_id
            if (!$this->indexExists('inscriptions', 'inscriptions_brand_id_index')) {
                $table->index('brand_id', 'inscriptions_brand_id_index');
            }
            
            // Verificar y crear índice compuesto brand_id, updated_at
            if (!$this->indexExists('inscriptions', 'inscriptions_brand_id_updated_at_index')) {
                $table->index(['brand_id', 'updated_at'], 'inscriptions_brand_id_updated_at_index');
            }
            
            // Verificar y crear índice compuesto brand_id, id
            if (!$this->indexExists('inscriptions', 'inscriptions_brand_id_id_index')) {
                $table->index(['brand_id', 'id'], 'inscriptions_brand_id_id_index');
            }
        });

        // Paso 6: Añadir foreign key (verificando que no exista)
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'inscriptions' 
            AND COLUMN_NAME = 'brand_id' 
            AND REFERENCED_TABLE_NAME = 'brands'
        ");
        
        if (empty($foreignKeyExists)) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->foreign('brand_id', 'inscriptions_brand_id_foreign')
                    ->references('id')
                    ->on('brands')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar foreign key si existe
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'inscriptions' 
            AND COLUMN_NAME = 'brand_id' 
            AND REFERENCED_TABLE_NAME = 'brands'
        ");
        
        if (!empty($foreignKeyExists)) {
            Schema::table('inscriptions', function (Blueprint $table) use ($foreignKeyExists) {
                $table->dropForeign($foreignKeyExists[0]->CONSTRAINT_NAME);
            });
        }

        // Eliminar índices si existen
        Schema::table('inscriptions', function (Blueprint $table) {
            if ($this->indexExists('inscriptions', 'inscriptions_brand_id_index')) {
                $table->dropIndex('inscriptions_brand_id_index');
            }
            if ($this->indexExists('inscriptions', 'inscriptions_brand_id_updated_at_index')) {
                $table->dropIndex('inscriptions_brand_id_updated_at_index');
            }
            if ($this->indexExists('inscriptions', 'inscriptions_brand_id_id_index')) {
                $table->dropIndex('inscriptions_brand_id_id_index');
            }
        });

        // Eliminar columna si existe
        if (Schema::hasColumn('inscriptions', 'brand_id')) {
            Schema::table('inscriptions', function (Blueprint $table) {
                $table->dropColumn('brand_id');
            });
        }
    }

    /**
     * Verificar si un índice existe
     */
    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};