<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tpv;

class TpvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'omnipay_type' => 'required|string|in:' . implode(',', array_keys(Tpv::TPV_TYPES)),
            'config' => 'required|array|min:1',
            'config.*.key' => 'required|string',
            'config.*.value' => 'required|string',
        ];
    }

}
