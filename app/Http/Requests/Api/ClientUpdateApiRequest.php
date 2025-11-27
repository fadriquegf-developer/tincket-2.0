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
            "name" => "required|string|max:255",
            "surname" => "required|string|max:255",
            "email" => [
                "required",
                "email",
                "confirmed"
            ],
            "phone" => "required|string|max:20",
            "email_confirmation" => "required|email",

            // ✅ AÑADIDOS: Campos opcionales
            "newsletter" => "nullable|boolean",
            "date_birth" => "nullable|date",
            "dni" => "nullable|string|max:20",
            "province" => "nullable|string|max:100",
            "city" => "nullable|string|max:100",
            "address" => "nullable|string|max:255",
            "postal_code" => "nullable|string|max:10",
            "mobile_phone" => "nullable|string|max:20",
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
