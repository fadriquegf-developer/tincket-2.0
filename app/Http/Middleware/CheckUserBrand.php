<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckUserBrand
{
    /**
     * Maneja la solicitud entrante verificando que el usuario (o el que intenta loguear)
     * pertenezca a la marca (brand) actual.
     *
     * @param  Request  $request
     * @param  \Closure(Request): mixed  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $brand = get_current_brand();
        $user  = Auth::guard('backpack')->user();

        // Caso 1: Durante el intento de login (POST en la ruta de login)
        if (
            !$user &&
            $request->isMethod('post') &&
            $request->routeIs('backpack.auth.login')
        ) {
            $email = $request->input('email');

            if ($brand && $email) {
                $userByEmail = User::where('email', $email)->first();

                if ($userByEmail && !$brand->users()->where('user_id', $userByEmail->id)->exists()) {
                    session()->flash('access_error', 'No tienes acceso a este cliente.');
                    return redirect()->route('backpack.auth.login');
                }
            }

            return $next($request);
        }

        // Caso 2: Después del login (usuario autenticado)
        if ($user && $brand) {
            // Se limpia la relación para asegurar que la consulta sea fresca
            $brand->unsetRelation('users');
            $hasAccess = $brand->users()->where('user_id', $user->id)->exists();

            if (!$hasAccess) {
                Auth::guard('backpack')->logout();
                session()->flash('access_error', 'No tienes acceso a este cliente.');
                return redirect()->route('backpack.auth.login');
            }
        }

        return $next($request);
    }
}
