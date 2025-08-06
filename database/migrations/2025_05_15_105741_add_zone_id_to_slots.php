<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->nullable()->after('space_id');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('set null');
        });

        // Copiar desde el pivot: espacio->configuración->detalle
        DB::statement(<<<SQL
            UPDATE slots s
            JOIN space_configuration_details scd
              ON scd.slot_id = s.id
            JOIN space_configurations sc
              ON sc.id = scd.space_configuration_id
            SET s.zone_id = scd.zone_id
            WHERE sc.space_id = s.space_id;
        SQL
        );
    }

    public function down()
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};
