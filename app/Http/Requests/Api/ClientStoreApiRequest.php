<?php

namespace App\Http\Requests\Api;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class ClientStoreApiRequest extends FormRequest
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
        return array_merge([
            "name" => "required",
            "surname" => "required",
            "email" => "required|email|confirmed",
            "email_confirmation" => "required",
            "password_confirmation" => "required",
            "phone" => "required",
                ], (new ChangePasswordApiRequest)->rules());
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator)
        {
            if ($this->emailExists($validator))
            {
                $validator->errors()->add('email', 'Email already exists!');
            }
        });
    }

    /**
     * Determine if the user/client exists in a
     * per brand filter basis.
     *
     * @return bool
     */
    public function emailExists($validator)
    {
        $brand_id = request()->get('brand')->id;
        $email = $this->request->get('email');

        return Client::where('brand_id', $brand_id)
                        ->where('email', $email)
                        ->exists();
    }

}
