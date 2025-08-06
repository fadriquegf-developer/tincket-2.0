<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddZonePermissionsToPermissionsTable extends Migration
{
    protected $permissions = [
        'zones.index',
        'zones.show',
        'zones.create',
        'zones.edit',
        'zones.delete',
    ];

    public function up()
    {
        $now = now();
        $rows = array_map(function($name) use ($now) {
            return [
                'name'       => $name,
                'guard_name' => 'backpack',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $this->permissions);

        DB::table('permissions')->insert($rows);

        foreach ($this->permissions as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'backpack')
                ->value('id');

            if ($permissionId) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'model_type'    => 'App\Models\User',
                    'model_id'      => 1,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('permissions')
            ->whereIn('name', $this->permissions)
            ->delete();
    }
}
