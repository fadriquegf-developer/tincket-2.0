<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddControlFieldsToTpvsTable extends Migration
{
    public function up()
    {
        Schema::table('tpvs', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('config');
            $table->boolean('is_test_mode')->default(false)->after('is_active'); 
            $table->boolean('is_default')->default(false)->after('is_test_mode');
            $table->integer('priority')->default(0)->after('is_default');
            
            $table->index(['brand_id', 'is_active', 'is_default']);
        });

        $this->markTestTpvs();
        $this->setDefaultTpvs();
    }

    private function markTestTpvs()
    {
        // Marcar TPVs de prueba basándose en el nombre y configuración
        DB::statement("
            UPDATE tpvs 
            SET is_test_mode = 1 
            WHERE (config LIKE '%sermepaTestMode%value%1%'
               OR LOWER(name) LIKE '%prueba%' 
               OR LOWER(name) LIKE '%proves%'
               OR LOWER(name) LIKE '%test%'
               OR LOWER(name) LIKE '%modo test%')
            AND deleted_at IS NULL
        ");

        // Lista específica de MerchantKeys de prueba conocidos
        $testMerchantKeys = ['sq7HjrUOBfKmC576ILgskD5srU870gJ7'];
        
        foreach ($testMerchantKeys as $key) {
            DB::statement("
                UPDATE tpvs 
                SET is_test_mode = 1 
                WHERE config LIKE ?
                AND deleted_at IS NULL
            ", ['%' . $key . '%']);
        }
    }

    private function setDefaultTpvs() 
    {
        // Para cada brand, marcar como default el TPV más usado (con más sesiones)
        $sql = "
            UPDATE tpvs t1
            INNER JOIN (
                SELECT tpv.id
                FROM tpvs tpv
                LEFT JOIN sessions s ON s.tpv_id = tpv.id AND s.deleted_at IS NULL
                WHERE tpv.deleted_at IS NULL
                AND tpv.is_test_mode = 0
                GROUP BY tpv.brand_id, tpv.id
                ORDER BY tpv.brand_id, COUNT(s.id) DESC, tpv.id ASC
            ) t2 ON t1.id = t2.id
            SET t1.is_default = 1
        ";
        
        DB::statement($sql);

        // Para brands sin sesiones, marcar el primer TPV no-test como default
        $brandsWithoutDefault = DB::select("
            SELECT DISTINCT brand_id 
            FROM tpvs 
            WHERE deleted_at IS NULL 
            AND brand_id NOT IN (
                SELECT brand_id FROM tpvs WHERE is_default = 1 AND deleted_at IS NULL
            )
        ");

        foreach ($brandsWithoutDefault as $brand) {
            DB::table('tpvs')
                ->where('brand_id', $brand->brand_id)
                ->whereNull('deleted_at')
                ->where('is_test_mode', false)
                ->orderBy('id')
                ->limit(1)
                ->update(['is_default' => true]);
        }
    }

    public function down()
    {
        Schema::table('tpvs', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_test_mode', 'is_default', 'priority']);
        });
    }
}