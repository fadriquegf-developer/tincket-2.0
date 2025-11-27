<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsedAtToCodesTable extends Migration
{
    public function up()
    {
        Schema::table('codes', function (Blueprint $table) {
            if (!Schema::hasColumn('codes', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('promotor_id');
            }
        });
    }

    public function down()
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->dropColumn('used_at');
        });
    }
}
