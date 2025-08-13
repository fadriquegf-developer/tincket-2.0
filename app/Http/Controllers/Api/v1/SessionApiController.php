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
        // Cargar la sesión con todas las relaciones necesarias
        $session = Session::with(['space.zones.slots', 'all_rates.rate'])
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

        // Construir configuración
        $configuration = [
            'id' => $session->id,
            'space_id' => $session->space->id,
            'name' => $session->space->name,
            'description' => $session->space->description ?? '',
            'session_id' => $session->id,
            'numbered' => $session->is_numbered,
            'free_positions' => $session->count_free_positions,
            'zoom' => $session->space->zoom ?? false,
            'zones' => $session->space->zones->map(function ($zone) use ($session) {
                // Obtener tarifas asignadas a esta zona
                $zoneRates = \App\Models\AssignatedRate::with('rate')
                    ->where('assignated_rate_type', \App\Models\Zone::class)
                    ->where('assignated_rate_id', $zone->id)
                    ->where('session_id', $session->id)
                    ->where('is_public', true)
                    ->get();

                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'space_id' => $zone->space_id,
                    'slots' => $zone->slots->map(function ($slot) use ($zoneRates, $session, $zone) {
                        // IMPORTANTE: Establecer pivot_session_id para que funcione la relación de rates
                        $slot->pivot_session_id = $session->id;
                        $slot->pivot_zone_id = $zone->id;

                        // Obtener el estado del slot desde la caché si existe
                        $cachedSlot = \App\Models\CacheSessionSlot::where('session_id', $session->id)
                            ->where('slot_id', $slot->id)
                            ->first();

                        $isLocked = false;
                        $lockReason = null;
                        $comment = null;

                        if ($cachedSlot) {
                            $isLocked = $cachedSlot->is_locked;
                            $comment = $cachedSlot->comment;
                            // Determinar lock_reason basado en el estado
                            if ($isLocked && $cachedSlot->cart_id) {
                                $cart = \App\Models\Cart::find($cachedSlot->cart_id);
                                if ($cart && $cart->confirmation_code) {
                                    $lockReason = 2; // Vendido
                                } else {
                                    $lockReason = 3; // Reservado
                                }
                            }
                        }

                        // Verificar si hay SessionSlot con estado
                        $sessionSlot = \App\Models\SessionSlot::where('session_id', $session->id)
                            ->where('slot_id', $slot->id)
                            ->first();

                        if ($sessionSlot && $sessionSlot->status_id) {
                            $isLocked = true;
                            $lockReason = $sessionSlot->status_id;
                            $comment = $sessionSlot->comment ?? $comment;
                        }

                        // Mapear las tarifas de la zona al slot
                        $slotRates = $zoneRates->map(function ($assignatedRate) use ($session, $zone) {
                            return [
                                'id' => $assignatedRate->rate->id,
                                'brand_id' => $assignatedRate->rate->brand_id,
                                'name' => $assignatedRate->rate->name,
                                'needs_code' => $assignatedRate->rate->needs_code,
                                'has_rule' => $assignatedRate->rate->has_rule,
                                'rule_parameters' => $assignatedRate->rate->rule_parameters,
                                'pivot' => [
                                    'assignated_rate_id' => $assignatedRate->id,
                                    'rate_id' => $assignatedRate->rate_id,
                                    'price' => $assignatedRate->price,
                                    'session_id' => $session->id,
                                    'max_on_sale' => $assignatedRate->max_on_sale,
                                    'max_per_order' => $assignatedRate->max_per_order,
                                    'assignated_rate_type' => 'App\\Models\\Zone',
                                    'is_public' => $assignatedRate->is_public,
                                    'is_private' => $assignatedRate->is_private,
                                    'max_per_code' => $assignatedRate->max_per_code,
                                    'zone_id' => $zone->id,
                                    'validator_class' => $assignatedRate->validator_class,
                                ]
                            ];
                        });

                        return [
                            'id' => $slot->id,
                            'zone_id' => $slot->zone_id,
                            'is_locked' => $isLocked,
                            'lock_reason' => $lockReason,
                            'name' => $slot->name,
                            'comment' => $comment,
                            'x' => $slot->x,
                            'y' => $slot->y,
                            'rates' => $slotRates->values(),
                        ];
                    })->values(),
                ];
            })->values(),
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
