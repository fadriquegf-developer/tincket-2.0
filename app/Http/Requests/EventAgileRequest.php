<?php

namespace App\Http\Requests;

use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class EventAgileRequest extends FormRequest
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
            'publish_on' => 'required',
            'location_name' => 'required_without:include_space',
            'address' => 'required_without:include_space',
            'postal_code' => 'required_without:include_space|numeric',
            'city_id' => 'required_without:include_space',
            'space_name' => 'required_without:include_space',
            'capacity' => 'required_without:include_space|numeric',
        ];
    }

}
