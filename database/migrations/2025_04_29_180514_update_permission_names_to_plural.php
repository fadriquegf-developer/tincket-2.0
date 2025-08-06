<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdatePermissionNamesToPlural extends Migration
{
    public function up()
    {
        // Renombrar permisos existentes
        $renames = [
            'post' => 'posts',
            'page' => 'pages',
            'taxonomy' => 'taxonomies',
            'menu_item' => 'menu_items',
            'mailing' => 'mailings',
        ];

        foreach ($renames as $singular => $plural) {
            DB::table('permissions')
                ->where('name', 'like', "$singular.%")
                ->get()
                ->each(function ($perm) use ($singular, $plural) {
                    $newName = str_replace("$singular.", "$plural.", $perm->name);
                    DB::table('permissions')->where('id', $perm->id)->update(['name' => $newName]);
                });
        }

        // Añadir y asignar permisos para form_fields
        $formFieldPermissions = ['index', 'show', 'create', 'edit', 'delete'];

        foreach ($formFieldPermissions as $action) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => "form_fields.$action",
                'guard_name' => 'backpack',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar al usuario ID 1
            DB::table('model_has_permissions')->insert([
                'permission_id' => $permissionId,
                'model_type' => 'App\\Models\\User',
                'model_id' => 1,
            ]);
        }
    }

    public function down()
    {
        // Revertir nombres a singular
        $renames = [
            'posts' => 'post',
            'pages' => 'page',
            'taxonomies' => 'taxonomy',
            'menu_items' => 'menu_item',
            'mailings' => 'mailing',
        ];

        foreach ($renames as $plural => $singular) {
            DB::table('permissions')
                ->where('name', 'like', "$plural.%")
                ->get()
                ->each(function ($perm) use ($singular, $plural) {
                    $oldName = str_replace("$plural.", "$singular.", $perm->name);
                    DB::table('permissions')->where('id', $perm->id)->update(['name' => $oldName]);
                });
        }

        // Eliminar los de form_fields
        DB::table('permissions')->where('name', 'like', 'form_fields.%')->delete();
    }
}
