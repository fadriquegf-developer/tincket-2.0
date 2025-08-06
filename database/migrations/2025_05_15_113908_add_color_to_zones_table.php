<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('color', 7) // ej. "#FFAA00"
                  ->default('#cccccc')
                  ->after('name');
        });
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
