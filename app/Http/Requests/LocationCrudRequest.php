<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class LocationCrudRequest extends FormRequest
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
            'name' => 'required',
            'address' => 'required',
            'postal_code' => 'required|numeric',
            'city_id' => 'required',
        ];
    }

}
