<?php

namespace App\Http\Requests;

use App\Models\Brand;
use App\Models\Capability;
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

        $rules = [
            'name'          => 'required|string|max:255',
            'code_name'     => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('brands', 'code_name')->ignore($brandId),
            ],
            'allowed_host'  => [Rule::unique('brands', 'allowed_host')->ignore($brandId)],
            'capability_id' => 'required|exists:capabilities,id',
            'parent_id'     => 'nullable|exists:brands,id',
        ];

        // Si se selecciona un capability que es promotor (id 3)
        if ($this->input('capability_id') == 3) {
            $rules['parent_id'] = [
                'required',
                Rule::exists('brands', 'id')->where(function ($query) {
                    $query->where('capability_id', 2); // Solo marcas con capability basic (id 2)
                })
            ];
        }

        return $rules;
    }


    public function messages()
    {
        return [
            'name.required' => __('validation.brand.name.required'),
            'code_name.required' => __('validation.brand.code_name.required'),
            'code_name.alpha_dash' => __('validation.brand.code_name.alpha_dash'),
            'code_name.unique' => __('validation.brand.code_name.unique'),
            'allowed_host.unique' => __('validation.brand.allowed_host.unique'),
            'capability_id.required' => __('validation.brand.capability_id.required'),
            'capability_id.exists' => __('validation.brand.capability_id.exists'),
            'parent_id.exists' => __('validation.brand.parent_id.exists'),
        ];
    }
}
