<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('carts')
            ->where('seller_type', 'App\\User')
            ->update(['seller_type' => 'App\\Models\\User']);

        DB::table('carts')
            ->where('seller_type', 'App\\Application')
            ->update(['seller_type' => 'App\\Models\\Application']);
    }

    public function down(): void
    {
        DB::table('carts')
            ->where('seller_type', 'App\\Models\\User')
            ->update(['seller_type' => 'App\\User']);

        DB::table('carts')
            ->where('seller_type', 'App\\Models\\Application')
            ->update(['seller_type' => 'App\\Application']);
    }
};