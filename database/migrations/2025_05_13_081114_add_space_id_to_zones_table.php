<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSpaceIdToZonesTable extends Migration
{
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->unsignedInteger('space_id')->nullable()->after('id');
            $table->foreign('space_id')
                ->references('id')->on('spaces')
                ->onDelete('cascade');
        });

        // Actualizar space_id en zones basado en relaciones antiguas
        $zones = DB::table('zones')->get();

        foreach ($zones as $zone) {
            // Buscar en space_configuration_details la relación con space_configuration_id
            $detail = DB::table('space_configuration_details')
                ->where('zone_id', $zone->id) // zone_id es id en zones
                ->first();

            if ($detail) {
                // Con space_configuration_id, buscar el space_id real en space_configurations
                $spaceConfig = DB::table('space_configurations')
                    ->where('id', $detail->space_configuration_id)
                    ->first();

                if ($spaceConfig && $spaceConfig->space_id) {
                    // Actualizar space_id en zones
                    DB::table('zones')
                        ->where('id', $zone->id)
                        ->update(['space_id' => $spaceConfig->space_id]);
                }
            }
        }
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['space_id']);
            $table->dropColumn('space_id');
        });
    }
}

