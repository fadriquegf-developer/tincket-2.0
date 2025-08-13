<?php

namespace App\Http\Requests;

use App\Rules\GreaterThanFieldOrEqual;
use Backpack\CRUD\app\Http\Requests\CrudRequest as FormRequest;

class PackCrudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required'],
            'starts_on'     => ['required'],
            'min_per_cart'  => ['required', 'integer'],
            'max_per_cart'  => [
                'required', 'integer',
                new GreaterThanFieldOrEqual('min_per_cart'),
            ],
            'rules'    => ['required', 'array', 'min:1'],
            'sessions' => ['required', 'array', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rules'    => json_decode($this->input('rules')    ?? '[]', true), // arrays
            'sessions' => json_decode($this->input('sessions') ?? '[]', true),
        ]);
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $rules = $validator->getData()['rules'] ?? [];

            foreach ($rules as $rule) {      // $rule es array
                $allSessions   = $rule['all_sessions']    ?? false;
                $numberSession = $rule['number_sessions'] ?? null;
                $percentPack   = $rule['percent_pack']    ?? null;
                $pricePack     = $rule['price_pack']      ?? null;

                // 1. Nº sesiones válido cuando no es "all remaining"
                if (!$allSessions && (!is_numeric($numberSession) || $numberSession === '')) {
                    $validator->errors()->add('rules', 'Sessions amount has to be numeric');
                }

                // 2. No permitir percent y price a la vez (>0)
                if (($percentPack ?? 0) > 0 && ($pricePack ?? 0) > 0) {
                    $validator->errors()->add('rules', 'Only percent OR price should be applied in one single rule');
                }

                // 3. Cualquier valor numérico debe ser realmente numérico
                if (
                    ($percentPack !== null && $percentPack !== '' && !is_numeric($percentPack)) ||
                    ($pricePack   !== null && $pricePack   !== '' && !is_numeric($pricePack))
                ) {
                    $validator->errors()->add('rules', 'Only numeric values in pack rules are accepted');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name'          => __('backend.pack.packname'),
            'starts_on'     => __('backend.pack.startson'),
            'min_per_cart'  => __('backend.pack.minpercart'),
            'max_per_cart'  => __('backend.pack.maxpercart'),
            'rules'         => __('backend.pack.rules'),
            'sessions'      => __('backend.pack.sessionamounts'),
        ];
    }
}
