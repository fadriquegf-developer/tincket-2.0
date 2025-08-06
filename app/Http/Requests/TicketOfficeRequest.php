<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class TicketOfficeRequest extends FormRequest
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
            'client_email' => 'sometimes|email',
        ];
    }

    protected function createDefaultValidator(\Illuminate\Contracts\Validation\Factory $factory)
    {
        $validator = parent::createDefaultValidator($factory);

        // we required name and lastname only if client's email is supplied
        $validator->sometimes(['client_firstname', 'client_lastname'], 'required', function ($input)
        {
            return !empty($input->client_email);
        });

        return $validator;
    }

}
