<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::table('assignated_rates')
            ->where('assignated_rate_type', 'App\\Session')
            ->update(['assignated_rate_type' => \App\Models\Session::class]);

        DB::table('assignated_rates')
            ->where('assignated_rate_type', 'App\\Zone')
            ->update(['assignated_rate_type' => \App\Models\Zone::class]);

    }

    public function down()
    {
        DB::table('assignated_rates')
            ->where('assignated_rate_type', \App\Models\Session::class)
            ->update(['assignated_rate_type' => 'App\\Session']);

        DB::table('assignated_rates')
            ->where('assignated_rate_type', \App\Models\Zone::class)
            ->update(['assignated_rate_type' => 'App\\Zone']);
    }
};
