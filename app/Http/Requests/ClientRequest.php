<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'surname'     => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:clients,email,' . $this->id,
            'phone'       => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'province'    => 'nullable|string|max:100',
            'city'        => 'nullable|string|max:100',
            'dni'         => 'nullable|string|max:20',
            'date_birth'  => 'nullable|date',
            'newsletter'  => 'nullable|boolean',
        ];

        // reglas para la contraseña
        $passwordRules = [
            'string',
            'min:8',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            'confirmed',      
        ];

        // Si es un alta (POST → store) la contraseña es obligatoria,
        // si es una edición (PUT/PATCH → update) es opcional.
        if ($this->isMethod('post')) {          // create
            array_unshift($passwordRules, 'required');
        } else {                                // update
            array_unshift($passwordRules, 'nullable');
        }

        $rules['password'] = $passwordRules;

        return $rules;
    }

}
