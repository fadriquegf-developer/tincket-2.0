<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class CensuCrudRequest extends FormRequest
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
            'name' => 'nullable',
            'code' => 'required'
        ];
    }

    public function attributes(): array
    {
        return [
            'code'          => __('backend.censu.code'),
            
        ];
    }

}
