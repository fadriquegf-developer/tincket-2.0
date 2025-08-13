<?php

namespace App\Http\Requests;

use App\Rules\MinImageWidth;
use Illuminate\Foundation\Http\FormRequest;
use Backpack\Pro\Uploads\Validation\ValidDropzone;

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
            'images' => ValidDropzone::field()->file(['dimensions:min_width=1200']),
            'custom_logo'  => ['nullable', new MinImageWidth(120)],
            'banner' =>  ['nullable', new MinImageWidth(1200)],
        ];

    }

}
