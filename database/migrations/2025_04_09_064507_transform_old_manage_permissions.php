<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up()
    {
        // 1. Recuperar todos los permisos que siguen el patrón 'manage_xxx'
        $oldPermissions = Permission::where('name', 'like', 'manage_%')->get();

        foreach ($oldPermissions as $oldPermission) {
            // Extraemos el sufijo: de 'manage_roles' obtenemos 'roles'
            $resource = substr($oldPermission->name, strlen('manage_'));

            // Definimos las nuevas acciones
            $newPermissions = [
                "{$resource}.index",
                "{$resource}.show",
                "{$resource}.create",
                "{$resource}.edit",
                "{$resource}.delete",
            ];

            // Creamos los nuevos permisos si no existen ya
            foreach ($newPermissions as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $oldPermission->guard_name,
                ]);
            }

            // 2. Buscamos los roles que tenían asignado este permiso antiguo
            $roles = Role::whereHas('permissions', function ($q) use ($oldPermission) {
                $q->where('id', $oldPermission->id);
            })->get();

            // 3. A cada uno de esos roles le asignamos los nuevos permisos
            foreach ($roles as $role) {
                $role->givePermissionTo($newPermissions);
            }

            // 4. Eliminamos la relación del permiso antiguo con esos roles
            DB::table('role_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->delete();

            // 5. Eliminamos el permiso antiguo de la tabla permissions
            $oldPermission->delete();
        }

        // 6. Limpiamos la caché de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        // REVERTIR los cambios no es trivial, pues se necesitaría restaurar
        // los permisos "manage_xxx" y quitar los cuatro permisos nuevos.
        // Aquí un ejemplo muy simplificado (tómalo con cautela, podrías perder 
        // información si en la aplicación ya se usan estos permisos nuevos en producción).

        // Este ejemplo re-crearía un permiso 'manage_{resource}' por cada
        // bloque de cuatro permisos, se los asignaría a roles y borraría
        // los cuatro permisos. Ajusta la lógica a tus necesidades reales.

        // 1. Recuperar todos los permisos que siguen el patrón '{resource}.xxx'
        // Asumimos que cualquier cosa con formato (algo).(index|create|edit|delete) 
        // debe revertirse a manage_(algo).
        $allPermissions = Permission::where('name', 'regexp', '^[^.]+\.(index|create|edit|delete)$')->get();

        // Organizar por recurso, p.e. "roles.index", "roles.create", etc. => "roles"
        $resources = [];
        foreach ($allPermissions as $perm) {
            // "roles.index" => ["roles", "index"]
            [$res] = explode('.', $perm->name);
            $resources[$res][] = $perm;
        }

        foreach ($resources as $res => $perms) {
            // 2. Creamos el permiso "manage_{resource}" si no existe
            $oldPermission = Permission::firstOrCreate([
                'name' => "manage_{$res}",
                'guard_name' => $perms[0]->guard_name ?? 'web',
            ]);

            // 3. Encontrar todos los roles que tengan los cuatro permisos
            foreach ($perms as $p) {
                $roles = Role::whereHas('permissions', function ($q) use ($p) {
                    $q->where('id', $p->id);
                })->get();

                // 4. A cada uno de esos roles le asignamos el permiso "manage_{res}"
                foreach ($roles as $r) {
                    $r->givePermissionTo($oldPermission);
                }
            }

            // 5. Eliminamos las relaciones de los nuevos permisos con cada rol
            DB::table('role_has_permissions')
                ->whereIn('permission_id', array_map(fn($p) => $p->id, $perms))
                ->delete();

            // 6. Borramos los permisos nuevos
            foreach ($perms as $p) {
                $p->delete();
            }
        }

        // 7. Limpiamos la caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
