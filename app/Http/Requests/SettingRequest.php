<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $settingId = $this->route('id');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_\.-]+$/i', // Solo permitir formato válido
                Rule::unique('settings')->where(function ($query) {
                    return $query->where('brand_id', get_current_brand_id());
                })->ignore($settingId),
            ],
            'value' => 'nullable|string|max:65535',
            'category' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => __('backend.settings_tpv.key_regex'),
            'key.unique' => __('backend.settings_tpv.key_unique'),
            'key.required' => __('backend.settings_tpv.key_required'),
            'key.max' => __('backend.settings_tpv.key_max'),
            'value.max' => __('backend.settings_tpv.value_max'),
            'category.max' => __('backend.settings_tpv.category_max'),
        ];
    }
}
