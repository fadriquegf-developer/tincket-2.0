<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('classifiables')
            ->where('classifiable_type', 'App\\Event')
            ->update(['classifiable_type' => 'App\\Models\\Event']);

        DB::table('classifiables')
            ->where('classifiable_type', 'App\\Post')
            ->update(['classifiable_type' => 'App\\Models\\Post']);
    }

    public function down(): void
    {
        DB::table('classifiables')
            ->where('classifiable_type', 'App\\Models\\Event')
            ->update(['classifiable_type' => 'App\\Event']);

        DB::table('classifiables')
            ->where('classifiable_type', 'App\\Models\\Post')
            ->update(['classifiable_type' => 'App\\Post']);
    }
};
