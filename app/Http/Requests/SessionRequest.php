<?php

namespace App\Http\Requests;

use App\Rules\MinImageWidth;
use Illuminate\Validation\Validator;
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
            'is_numbered' => 'required',
            'autolock_n' => 'required|integer|min:0',
            'code_type' => 'required|in:null,session,census,user',
            'images' => ValidDropzone::field()->file(['dimensions:min_width=1200']),
            'custom_logo' => ['nullable', new MinImageWidth(120)],
            'banner' => ['nullable', new MinImageWidth(1200)],
            'starts_on' => 'required',
            'ends_on' => 'required|after:starts_on',
            'inscription_starts_on' => 'required',
            'inscription_ends_on' => 'required|after:inscription_starts_on',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Solo validar en update (cuando existe el campo rates)
            if (!$this->has('rates')) {
                return;
            }

            $isNumbered = (bool) $this->input('is_numbered');
            
            // Solo validar si la sesión es numerada
            if (!$isNumbered) {
                return;
            }

            $ratesJson = $this->input('rates');
            $rates = is_string($ratesJson) ? json_decode($ratesJson, true) : $ratesJson;
            
            if (!is_array($rates) || empty($rates)) {
                return;
            }

            foreach ($rates as $index => $rate) {
                // Verificar si la tarifa no tiene zona asignada
                $zoneId = $rate['zone_id'] ?? null;
                
                if (empty($zoneId) || $zoneId === '' || $zoneId === 'null') {
                    $rateName = $rate['rate']['name'] ?? __('backend.cart.rate');
                    
                    $validator->errors()->add(
                        'rates',
                        __('La tarifa ":rate" requiere una zona asignada para sesiones numeradas.', [
                            'rate' => $rateName
                        ])
                    );
                }
            }
        });
    }

}
