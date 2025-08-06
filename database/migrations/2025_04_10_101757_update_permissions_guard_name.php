<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePermissionsGuardName extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambia el valor de guard_name de "web" a "backpack" en la tabla permissions.
     *
     * @return void
     */
    public function up()
    {
        DB::table('permissions')
            ->where('guard_name', 'web')
            ->update(['guard_name' => 'backpack']);
    }

    /**
     * Reverse the migrations.
     *
     * Reversa el cambio, volviendo a "web" desde "backpack".
     *
     * @return void
     */
    public function down()
    {
        DB::table('permissions')
            ->where('guard_name', 'backpack')
            ->update(['guard_name' => 'web']);
    }
}
