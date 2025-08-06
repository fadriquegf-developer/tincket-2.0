<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'event' => 'required',
            'space' => 'required',
            'autolock_n' => 'required|integer|min:0',
            'code_type' => 'required|in:null,session,census,user',
            'images.*' => 'dimensions:min_width=1200',
            'custom_logo' => 'nullable|dimensions:max_width=256',
            'banner' => 'nullable|dimensions:min_width=1200',
        ];

    }

}
