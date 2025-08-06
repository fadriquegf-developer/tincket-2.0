<?php

namespace App\Http\Requests\Api;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class ClientUpdateApiRequest extends FormRequest
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
            "name" => "required",
            "surname" => "required",
            "email" => [
                "required",
                "email",
                "confirmed"
                // TODO: Clean this.
                // This rule has been moved in a After Hook.
                // email duplicates will be verified only by API.
                //\Illuminate\Validation\Rule::unique('clients')->ignore($client_id->id)->where('brand_id', $brand_id);
                ],
            "phone" => "required",
            "email_confirmation" => "required"
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->emailExists($validator)) {
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
        $client_id = \Route::getCurrentRoute()->parameter('client')->id;
        $email = $this->request->get('email');

        return Client::where('brand_id', $brand_id)
            ->where('email', $email)
            ->where('id', '!=', $client_id)
            ->exists();
    }

}
