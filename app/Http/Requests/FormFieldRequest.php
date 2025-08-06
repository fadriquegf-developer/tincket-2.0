<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class FormFieldRequest extends FormRequest
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
        return [
            'label' => 'required',
            'name' => 'required',
            'type' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'label'         => __('backend.form_field.label'),
            'name'          => __('backend.form_field.name'),
        ];
    }

}