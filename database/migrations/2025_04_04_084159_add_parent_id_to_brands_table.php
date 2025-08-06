<?php

use App\Models\Brand;
use App\Models\Capability;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedInteger('parent_id')->nullable()->after('capability_id');
            $table->foreign('parent_id')->references('id')->on('brands')->onDelete('set null');
        });

        // Obtener el ID de la capability 'basic'
        $basicCapabilityId = Capability::where('code_name', 'basic')->first()?->id;

        // Obtener la primera brand con capability 'engine'
        $engineBrandId = Brand::where('capability_id', Capability::where('code_name', 'engine')->first()?->id)->first()?->id;

        // Asignar parent_id = engineBrandId a todas las marcas tipo 'basic'
        DB::table('brands')
            ->where('capability_id', $basicCapabilityId)
            ->update(['parent_id' => $engineBrandId]);

    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
