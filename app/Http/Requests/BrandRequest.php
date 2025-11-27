<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    public function authorize()
    {
        return backpack_auth()->check();
    }

    public function rules()
    {
        $brandId = $this->route('id') ?? $this->input('id');
        $isUpdating = !empty($brandId);

        $rules = [
            'name' => 'required|string|max:255',
            'code_name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                'regex:/^[a-z0-9_-]+$/', // Solo minúsculas, números, guiones
                Rule::unique('brands', 'code_name')->ignore($brandId),
            ],
            'allowed_host' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^([\da-z\.-]+\.)+[a-z]{2,}$/i', // Validación de dominio (sin protocolo)
                Rule::unique('brands', 'allowed_host')->ignore($brandId),
            ],
            'parent_id' => 'nullable|exists:brands,id',
        ];

        if (!$isUpdating) {
            $rules['capability_id'] = 'required|exists:capabilities,id';

            // Si se selecciona un capability que es promotor (id 3)
            if ($this->input('capability_id') == 3) {
                $rules['parent_id'] = [
                    'required',
                    Rule::exists('brands', 'id')->where(function ($query) {
                        $query->where('capability_id', 2);
                    })
                ];
            }
        }

        return $rules;
    }

    /**
     * Prepara los datos para validación
     */
    protected function prepareForValidation()
    {
        // Sanitizar y normalizar allowed_host
        if ($this->has('allowed_host') && !empty($this->allowed_host)) {
            $host = $this->allowed_host;

            // Eliminar espacios en blanco
            $host = trim($host);

            // Eliminar protocolo si lo tiene (http:// o https://)
            $host = preg_replace('/^https?:\/\//', '', $host);

            // Eliminar www. si lo tiene
            $host = preg_replace('/^www\./', '', $host);

            // Eliminar trailing slash y path
            $host = explode('/', $host)[0];

            // Convertir a minúsculas
            $host = strtolower($host);

            $this->merge(['allowed_host' => $host]);
        }

        // Sanitizar code_name: convertir a minúsculas y eliminar espacios
        if ($this->has('code_name')) {
            $this->merge([
                'code_name' => strtolower(str_replace(' ', '_', trim($this->code_name)))
            ]);
        }
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.brand.name.required'),
            'code_name.required' => __('validation.brand.code_name.required'),
            'code_name.alpha_dash' => __('validation.brand.code_name.alpha_dash'),
            'code_name.regex' => 'El código solo puede contener letras minúsculas, números, guiones y guiones bajos',
            'code_name.unique' => __('validation.brand.code_name.unique'),
            'allowed_host.regex' => 'El dominio debe ser válido (ej: ejemplo.com)',
            'allowed_host.unique' => __('validation.brand.allowed_host.unique'),
            'capability_id.required' => __('validation.brand.capability_id.required'),
            'capability_id.exists' => __('validation.brand.capability_id.exists'),
            'parent_id.exists' => __('validation.brand.parent_id.exists'),
            'parent_id.required' => 'El brand padre es requerido para capabilities de tipo promotor',
        ];
    }

    /**
     * Obtiene los datos validados y procesados
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        // Asegurar que allowed_host esté correctamente formateado
        if (isset($data['allowed_host']) && !empty($data['allowed_host'])) {
            $data['allowed_host'] = $this->sanitizeDomain($data['allowed_host']);
        }

        return $data;
    }

    /**
     * Sanitiza un dominio para uso seguro
     */
    private function sanitizeDomain($domain)
    {
        // Eliminar espacios
        $domain = trim($domain);

        // Eliminar protocolo si existe
        $domain = preg_replace('/^https?:\/\//', '', $domain);

        // Eliminar www.
        $domain = preg_replace('/^www\./', '', $domain);

        // Eliminar path y query strings
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];

        // Convertir a minúsculas
        $domain = strtolower($domain);

        // Validar que sea un dominio válido
        if (!preg_match('/^([\da-z\.-]+\.)+[a-z]{2,}$/i', $domain)) {
            return null;
        }

        return $domain;
    }
}
