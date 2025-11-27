<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'dni' => 'nullable|string|max:20',
            'date_birth' => 'nullable|date|before:today|after:1900-01-01',
            'newsletter' => 'nullable|boolean',
        ];

        // Validación de email con unicidad por brand_id
        $rules['email'] = [
            'required',
            'email',
            'max:255',
            Rule::unique('clients')
                ->where('brand_id', get_current_brand_id())
                ->ignore($this->id)
                ->whereNull('deleted_at') // Ignorar registros eliminados si usas SoftDeletes
        ];

        // Reglas mejoradas para password usando Laravel Password Rule
        if ($this->isMethod('post')) {
            // Crear cliente - password obligatorio
            $rules['password'] = [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // Verifica contra haveibeenpwned
            ];
        } else {
            // Actualizar cliente - password opcional
            $rules['password'] = [
                'nullable',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.client.name_required'),
            'surname.required' => __('validation.client.surname_required'),
            'email.required' => __('validation.client.email_required'),
            'email.email' => __('validation.client.email_invalid'),
            'email.unique' => __('validation.client.email_unique'),
            'password.required' => __('validation.client.password_required'),
            'password.confirmed' => __('validation.client.password_confirmed'),
            'password.min' => __('validation.client.password_min'),
            'password.mixed' => __('validation.client.password_mixed'),
            'password.numbers' => __('validation.client.password_numbers'),
            'password.symbols' => __('validation.client.password_symbols'),
            'password.uncompromised' => __('validation.client.password_uncompromised'),
            'date_birth.before' => __('validation.client.date_birth_before'),
            'date_birth.after' => __('validation.client.date_birth_after'),
        ];
    }
}