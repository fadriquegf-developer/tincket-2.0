<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class RegisterInputRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'name_form' => 'required',
            'type' => 'required',
        ];
    }

    public function attributes(): array
    {
        return [
            'name_form' => __('backend.register_input.name_form'),
            'type' => __('backend.register_input.type'),
        ];
    }

}
