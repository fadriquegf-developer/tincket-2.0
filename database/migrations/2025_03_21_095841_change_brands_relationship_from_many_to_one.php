<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeBrandsRelationshipFromManyToOne extends Migration
{
    public function up()
    {

        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedInteger('capability_id')->nullable()->after('type');
        });

        /* Esta funcion esta pensada para migrar la relacion n-m que habia antes, a una relacion 1-n */
        DB::statement('
            UPDATE brands
            JOIN (
                SELECT brand_id, MIN(capability_id) AS capability_id
                FROM brand_capability
                GROUP BY brand_id
            ) AS bc ON brands.id = bc.brand_id
            SET brands.capability_id = bc.capability_id
        ');

        Schema::table('brands', function (Blueprint $table) {
            $table->foreign('capability_id')
                ->references('id')
                ->on('capabilities')
                ->onDelete('cascade');
        });

        Schema::dropIfExists('brand_capability');
    }

    public function down()
    {
        Schema::create('brand_capability', function (Blueprint $table) {
            $table->unsignedInteger('brand_id');
            $table->unsignedInteger('capability_id');

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');
            $table->foreign('capability_id')
                ->references('id')
                ->on('capabilities')
                ->onDelete('cascade');

            $table->primary(['brand_id', 'capability_id']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['capability_id']);
            $table->dropColumn('capability_id');
        });
    }
}
