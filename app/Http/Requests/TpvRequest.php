<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tpv;
use Illuminate\Validation\Rule;

class TpvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $tpvId = $this->route('id');
        $brandId = get_current_brand_id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tpvs')
                    ->where('brand_id', $brandId)
                    ->whereNull('deleted_at')
                    ->ignore($tpvId)
            ],
            'omnipay_type' => 'required|string|in:' . implode(',', array_keys(Tpv::TPV_TYPES)),
            'config' => 'required|array|min:1',
            'config.*.key' => 'required|string',
            'config.*.value' => 'required|string',
        ];
    }
}
