<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Session;
use App\Http\Resources\SessionShowResource;

class SessionApiController extends \App\Http\Controllers\Api\ApiController
{

    /**
     * Display the specified resource.
     *
     * @param  Session  $session
     */
    public function show(int $session)
    {
        $builder = Session::with(['space.location', 'event']) // Cargamos las relaciones directamente
            ->where('id', $session);

        if (!request()->get('show_expired', false)) {
            $builder->where('ends_on', '>', \Carbon\Carbon::now())
                ->where('inscription_starts_on', '<', \Carbon\Carbon::now())
                ->where('inscription_ends_on', '>', \Carbon\Carbon::now())
                ->where(function ($query) {
                    $query->where('visibility', 1)
                        ->orWhere('private', 1);
                });
        }

        $session = $builder->firstOrFail();


        $session->checkBrandPartnership();

        // Optimizar los cálculos de posiciones disponibles utilizando caché o consultas agregadas
        $session->setAttribute('count_free_positions', $session->count_available_web_positions);
        $session->setAttribute('rates', $session->cascade_rates);

        return new SessionShowResource($session);
    }


    public function configuration(int $session_id)
    {
        // Cargar la sesión solo si show_expired es false
        $session = Session::with(['space.zones.slots'])
            ->where('id', $session_id)
            ->when(!request()->get('show_expired', false), function ($query) {
                $query->where('ends_on', '>', \Carbon\Carbon::now())
                    ->where(function ($query) {
                        $query->where('visibility', 1)
                            ->orWhere('private', 1);
                    });
            })
            ->firstOrFail();

        // Verificar la asociación con la marca
        $session->checkBrandPartnership();

        // Inicializar caché de slots si es numerado
        if ($session->is_numbered) {
            $slot_cache = new \App\Services\Api\SlotCacheService($session);
            $slot_cache->getSlotsState();
        }

        // Construir configuración dinámica con tarifas por slot en contexto de la sesión actual
        $configuration = [
            'id' => $session->id,
            'space_id' => $session->space->id,
            'name' => $session->space->name,
            'description' => $session->space->description ?? '',
            'session_id' => $session->id,
            'numbered' => $session->is_numbered,
            'zones' => $session->space->zones->map(function ($zone) use ($session) {

                // Obtener tarifas asignadas a esta zona y sesión
                $rates = \App\Models\AssignatedRate::with('rate')
                    ->where('assignated_rate_type', \App\Models\Zone::class)
                    ->where('assignated_rate_id', $zone->id)
                    ->where('session_id', $session->id)
                    ->get();

                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'space_id' => $zone->space_id,
                    'slots' => $zone->slots->map(function ($slot) use ($rates, $session) {
                        // Reutilizar las mismas tarifas para todos los slots de la zona
                        $slotRates = $rates
                            ->filter(fn($pivot) => $pivot->is_public) // solo tarifas públicas
                            ->map(function ($pivot) use ($session, $slot) {
                            return [
                                'id' => $pivot->rate->id,
                                'brand_id' => $pivot->rate->brand_id,
                                'name' => $pivot->rate->name,
                                'needs_code' => $pivot->rate->needs_code,
                                'has_rule' => $pivot->rate->has_rule,
                                'rule_parameters' => $pivot->rate->rule_parameters,
                                'pivot' => [
                                    'assignated_rate_id' => $pivot->id,
                                    'rate_id' => $pivot->rate_id,
                                    'price' => $pivot->price,
                                    'session_id' => $session->id,
                                    'max_on_sale' => $pivot->max_on_sale,
                                    'max_per_order' => $pivot->max_per_order,
                                    'assignated_rate_type' => 'App\\Models\\Zone',
                                    'is_public' => $pivot->is_public,
                                    'is_private' => $pivot->is_private,
                                    'max_per_code' => $pivot->max_per_code,
                                    'zone_id' => $pivot->zone_id,
                                    'validator_class' => $pivot->validator_class,
                                ]
                            ];
                        });

                        return [
                            'id' => $slot->id,
                            'zone_id' => $slot->zone_id,
                            'is_locked' => $slot->is_locked,
                            'name' => $slot->name,
                            'x' => $slot->x,
                            'y' => $slot->y,
                            'rates' => $slotRates,
                        ];
                    }),
                ];
            }),
        ];

        // Retornar configuración generada como JSON
        return response()->json([
            'data' => $configuration,
            'metadata' => [
                'cache' => ['force_clean' => false]
            ]
        ]);
    }


}
