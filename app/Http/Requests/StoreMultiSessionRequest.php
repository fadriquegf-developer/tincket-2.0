<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMultiSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'creation_mode' => 'required|in:season,specific_dates',
            'event_id'      => 'required|exists:events,id',
            'space_id'      => 'required|exists:spaces,id',
            'tpv_id'        => 'nullable|exists:tpvs,id',
            'max_places'    => 'required|integer|min:1',
            'is_numbered'   => 'boolean',
            'inscription_start' => 'required|date',
            'rates'         => 'required|array|min:1',
            'rates.*.rate_id'   => 'required|exists:rates,id',
            'rates.*.price'     => 'required|numeric|min:0',
            'rates.*.max_on_sale'   => 'nullable|integer|min:0',
            'rates.*.max_per_order' => 'nullable|integer|min:0',
            'rates.*.is_public'     => 'nullable|boolean',
        ];

        // ✅ Validaciones según modo
        if ($this->input('creation_mode') === 'season') {
            // MODO TEMPORADA: Requiere rango + días semana + templates
            $rules = array_merge($rules, [
                'season_start'  => 'required|date',
                'season_end'    => 'required|date|after_or_equal:season_start',
                'weekdays'      => 'required|array|min:1',
                'weekdays.*'    => 'in:1,2,3,4,5,6,7',
                'templates'     => 'required|array|min:1',
                'templates.*.title' => 'required|string|max:255',
                'templates.*.start' => 'required|date_format:H:i',
                'templates.*.end'   => 'required|date_format:H:i|after:templates.*.start',
            ]);
        } else {
            // MODO FECHAS ESPECÍFICAS: Requiere array de fechas concretas
            $rules = array_merge($rules, [
                'specific_dates'        => 'required|array|min:1',
                'specific_dates.*.date' => 'required|date',
                'specific_dates.*.title' => 'nullable|string|max:255',
                'specific_dates.*.start' => 'required|date_format:H:i',
                'specific_dates.*.end'   => 'required|date_format:H:i|after:specific_dates.*.start',
            ]);
        }

        // Zone_id OBLIGATORIO solo si is_numbered = true
        if ($this->input('is_numbered') == 1) {
            $rules['rates.*.zone_id'] = 'required|exists:zones,id';
        } else {
            $rules['rates.*.zone_id'] = 'nullable|exists:zones,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'creation_mode.required' => 'Debes seleccionar un modo de creación',
            'creation_mode.in' => 'Modo de creación inválido',

            'inscription_start.before_or_equal' =>
            __('backend.multi_session.alert_inscription_before'),

            'templates.*.end.after' =>
            __('backend.multi_session.msg_end_after_start'),

            'specific_dates.*.end.after' =>
            'La hora de fin debe ser posterior a la hora de inicio',

            'specific_dates.*.date.required' =>
            'Cada sesión debe tener una fecha',
        ];
    }

    public function attributes(): array
    {
        $t = __('backend.multi_session');

        return [
            'creation_mode'   => $t['creation_mode'] ?? 'Modo de creación',
            'event_id'        => $t['event'],
            'space_id'        => $t['space'],
            'season_start'    => $t['season_start'],
            'season_end'      => $t['season_end'],
            'inscription_start' => $t['sale_start'],
            'weekdays'        => $t['weekdays'],
            'max_places'      => $t['max_places'],
            'is_numbered'     => $t['numbered'],

            'templates.*.title' => $t['title'],
            'templates.*.start' => $t['start_time'],
            'templates.*.end'   => $t['end_time'],

            'specific_dates.*.date'  => $t['date'] ?? 'Fecha',
            'specific_dates.*.title' => $t['title'],
            'specific_dates.*.start' => $t['start_time'],
            'specific_dates.*.end'   => $t['end_time'],

            'rates.*.zone_id'  => $t['zone'],
            'rates.*.rate_id'  => $t['rate'],
            'rates.*.price'    => $t['price'],
            'rates.*.max_on_sale'   => $t['max_on_sale'],
            'rates.*.max_per_order' => $t['max_per_inscription'],
        ];
    }
}
