<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMultiSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();   // o tu lógica de permisos
    }

    /* ---------- 1. reglas ---------- */
    public function rules(): array
    {
        return [
            'event_id'      => 'required|exists:events,id',
            'space_id'      => 'required|exists:spaces,id',
            'tpv_id'        => 'nullable|exists:tpvs,id',
            'max_places'    => 'required|integer|min:1',
            'is_numbered'   => 'boolean',
            'season_start'  => 'required|date',
            'season_end'    => 'required|date|after_or_equal:season_start',
            'inscription_start' => 'required|date|before_or_equal:season_start',
            'weekdays'      => 'required|array|min:1',
            'weekdays.*'    => 'in:1,2,3,4,5,6,7',

            'templates'                 => 'required|array|min:1',
            'templates.*.title'         => 'required|string|max:255',
            'templates.*.start'         => 'required|date_format:H:i',
            'templates.*.end'           => 'required|date_format:H:i|after:templates.*.start',

            'rates'                     => 'required|array|min:1',
            'rates.*.zone_id'           => 'required|exists:zones,id',
            'rates.*.rate_id'           => 'required|exists:rates,id',
            'rates.*.price'             => 'required|numeric|min:0',
            'rates.*.max_on_sale'       => 'nullable|integer|min:0',
            'rates.*.max_per_order'     => 'nullable|integer|min:0',
        ];
    }

    /* ---------- 2. mensajes personalizados ---------- */
    public function messages(): array
    {
        return [
            'inscription_start.before_or_equal' =>
                __('backend.multi_session.alert_inscription_before'),

            'templates.*.end.after' =>
                __('backend.multi_session.msg_end_after_start'),
        ];
    }

    /* ---------- 3. nombres legibles ---------- */
    public function attributes(): array
    {
        $t = __('backend.multi_session');   // atajo

        return [
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

            'rates.*.zone_id'  => $t['zone'],
            'rates.*.rate_id'  => $t['rate'],
            'rates.*.price'    => $t['price'],
            'rates.*.max_on_sale'   => $t['max_on_sale'],
            'rates.*.max_per_order' => $t['max_per_inscription'],
        ];
    }
}

