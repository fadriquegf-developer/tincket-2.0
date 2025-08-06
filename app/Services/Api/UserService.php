<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends AbstractService
{

    /**
     * Crea un usuario a partir del alta de promotor y le asigna
     * los roles correspondientes.
     *
     * @param Request $request
     * @return User $user
     */
    public function createPromotorUser(Request $request)
    {
        // Validar que los datos necesarios estén presentes
        if (!$request->filled(['email', 'password', 'name', 'code_name'])) {
            throw new \InvalidArgumentException("Datos de usuario incompletos.");
        }

        // Usar transacciones para evitar un estado inconsistente
        return DB::transaction(function () use ($request) {
            $user = new User;
            $user->email = $request->email;
            $user->password = Hash::make($request->password); // Usar Hash::make
            $user->name = $request->name;
            unset($user->slug);
            $user->save();

            // Asignar roles
            $this->assignRolesToUser($user);

            return $user;
        });
    }

    /**
     * Asigna roles predefinidos a un usuario.
     *
     * @param User $user
     */
    private function assignRolesToUser(User $user)
    {
        $roles = ['admin', 'employee', 'manager', 'seller']; // Roles predefinidos
        $user->assignRole($roles);
    }
}
