<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Solo permitir si el usuario está autenticado
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = $this->route('id') ?? $this->id;

        // Reglas base para name y email
        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                // Permite casi cualquier símbolo común excepto los peligrosos
                'regex:/^[^<>{}[\]\\|`]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns', // Validación estricta con verificación DNS
                'max:255',
                'unique:users,email,' . $userId,
            ],
            'allowed_ips' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        // Validar formato de IPs (soporta IPs simples y rangos CIDR)
                        $ips = explode(',', $value);
                        foreach ($ips as $ip) {
                            $ip = trim($ip);
                            // Validar IP simple o rango CIDR
                            if (
                                !filter_var($ip, FILTER_VALIDATE_IP) &&
                                !preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/[0-9]{1,2}$/', $ip)
                            ) {
                                $fail(__('backend.user.validation.invalid_ip_format'));
                                break;
                            }
                        }
                    }
                },
            ],
        ];

        // Reglas de contraseña
        $passwordRules = $this->getPasswordRules();

        // Creación (POST) - contraseña requerida
        if ($this->isMethod('post')) {
            $rules['password'] = array_merge(['required'], $passwordRules);
        }
        // Actualización (PUT/PATCH) - contraseña opcional
        else {
            $rules['password'] = array_merge(['nullable'], $passwordRules);
        }

        return $rules;
    }

    /**
     * Obtiene las reglas de validación para la contraseña
     * 
     * @return array
     */
    protected function getPasswordRules()
    {
        return [
            'string',
            Password::min(8)
                ->max(128) // Evitar contraseñas excesivamente largas
                ->mixedCase() // Requiere mayúsculas y minúsculas
                ->numbers() // Requiere al menos un número
                ->symbols() // Requiere al menos un símbolo
                ->uncompromised(3), // Verifica en bases de datos de brechas (threshold 3)
            'confirmed',
            // Validación personalizada adicional
            function ($attribute, $value, $fail) {
                if (!$value)
                    return; // Skip si es nullable y está vacío
    
                // No permitir espacios en blanco
                if (preg_match('/\s/', $value)) {
                    $fail(__('backend.user.validation.password_no_spaces'));
                }

                // No permitir que contenga el username del email
                $email = $this->input('email');
                if ($email) {
                    $emailUsername = explode('@', $email)[0];
                    if (strlen($emailUsername) > 3 && stripos($value, $emailUsername) !== false) {
                        $fail(__('backend.user.validation.password_contains_email'));
                    }
                }

                // No permitir que contenga el nombre
                $name = $this->input('name');
                if ($name) {
                    // Dividir el nombre en palabras y verificar cada una
                    $nameParts = preg_split('/\s+/', $name);
                    foreach ($nameParts as $part) {
                        if (strlen($part) > 3 && stripos($value, $part) !== false) {
                            $fail(__('backend.user.validation.password_contains_name'));
                            break;
                        }
                    }
                }

                // Evitar patrones comunes
                $commonPatterns = [
                    '123456',
                    '12345678',
                    'qwerty',
                    'password',
                    'abc123',
                    '111111',
                    'admin',
                    'letmein',
                    'welcome',
                    'monkey',
                    'dragon',
                    'master'
                ];

                $lowerValue = strtolower($value);
                foreach ($commonPatterns as $pattern) {
                    if (strpos($lowerValue, $pattern) !== false) {
                        $fail(__('backend.user.validation.password_common_pattern'));
                        break;
                    }
                }

                // Evitar secuencias de teclado
                $keyboardSequences = [
                    'qwertyuiop',
                    'asdfghjkl',
                    'zxcvbnm',
                    'qweasd',
                    'asdqwe',
                    '1qaz2wsx'
                ];

                foreach ($keyboardSequences as $sequence) {
                    if (stripos($lowerValue, $sequence) !== false) {
                        $fail(__('backend.user.validation.password_keyboard_pattern'));
                        break;
                    }
                }
            }
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => __('backend.user.name'),
            'email' => __('backend.user.email'),
            'password' => __('backend.user.password'),
            'password_confirmation' => __('backend.user.password_confirmation'),
            'allowed_ips' => __('backend.user.allowed_ips'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => __('backend.user.validation.name_required'),
            'name.regex' => __('backend.user.validation.name_invalid'),
            'name.min' => __('backend.user.validation.name_min'),
            'name.max' => __('backend.user.validation.name_max'),

            'email.required' => __('backend.user.validation.email_required'),
            'email.email' => __('backend.user.validation.email_invalid'),
            'email.unique' => __('backend.user.validation.email_unique'),

            'password.required' => __('backend.user.validation.password_required'),
            'password.min' => __('backend.user.validation.password_min'),
            'password.mixed' => __('backend.user.validation.password_mixed_case'),
            'password.numbers' => __('backend.user.validation.password_numbers'),
            'password.symbols' => __('backend.user.validation.password_symbols'),
            'password.uncompromised' => __('backend.user.validation.password_compromised'),
            'password.confirmed' => __('backend.user.validation.password_confirmed'),
        ];
    }
}
