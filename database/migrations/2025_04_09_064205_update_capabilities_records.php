<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateCapabilitiesRecords extends Migration
{
    public function up()
    {
        // Id = 1: name -> 'Engine'
        DB::table('capabilities')
            ->where('id', 1)
            ->update(['name' => 'Engine']);

        // Id = 2: code_name -> 'basic', name -> 'Basico'
        DB::table('capabilities')
            ->where('id', 2)
            ->update([
                'code_name' => 'basic',
                'name' => 'Basico'
            ]);

        // Id = 3: name -> 'Promotor'
        DB::table('capabilities')
            ->where('id', 3)
            ->update(['name' => 'Promotor']);
    }

    public function down()
    {
        // Revertir los cambios (si fuera necesario restaurar el estado anterior):

        // Id = 1
        DB::table('capabilities')
            ->where('id', 1)
            ->update(['name' => json_encode(['en' => 'Engine', 'es' => 'Motor'])]);

        // Id = 2
        DB::table('capabilities')
            ->where('id', 2)
            ->update([
                'code_name' => 'basics',
                'name' => json_encode(['en' => 'Basics', 'es' => 'Básicas'])
            ]);

        // Id = 3
        DB::table('capabilities')
            ->where('id', 3)
            ->update(['name' => json_encode(['en' => 'Promoter', 'es' => 'Promotor'])]);
    }
}
