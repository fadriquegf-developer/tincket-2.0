<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;

class AssignAllPermissionsToSuperusers extends Migration
{
    /**
     * Run the migrations.
     *
     * Asigna todos los permisos existentes a los superusuarios definidos en config/superusers.php.
     *
     * @return void
     */
    public function up()
    {
        // Obtén la configuración de superusuarios
        $superUserIds = config('superusers.ids', []);

        // Obtén todos los permisos existentes (usando el modelo de Spatie)
        $allPermissions = Permission::all();

        // Obtén el modelo de usuario configurado en Backpack.
        $userModel = config('backpack.base.user_model_fqn');

        // Asigna todos los permisos a cada superusuario (si es que existe)
        foreach ($superUserIds as $userId) {
            $user = $userModel::find($userId);
            if ($user) {
                // Esto sincroniza (asigna) todos los permisos existentes al usuario.
                $user->syncPermissions($allPermissions);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Elimina todos los permisos de los superusuarios definidos.
     *
     * @return void
     */
    public function down()
    {
        // Obtén la configuración de superusuarios
        $superUserIds = config('superusers.ids', []);

        // Obtén el modelo de usuario configurado en Backpack.
        $userModel = config('backpack.base.user_model_fqn');

        // Revoca todos los permisos de cada superusuario
        foreach ($superUserIds as $userId) {
            $user = $userModel::find($userId);
            if ($user) {
                $user->syncPermissions([]);
            }
        }
    }
}
