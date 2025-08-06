<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PartnerStoreApiRequest extends FormRequest
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
        // password validation rules is taken from its own validator
        return [
            "name" => 'required',
            "email" => 'required|unique:users|email',
            "password" => "required|min:6|confirmed",
            "password_confirmation" => "required",
            'code_name' => 'required|unique:brands',
            'allowed_host' => 'required'
        ];
    }


}
