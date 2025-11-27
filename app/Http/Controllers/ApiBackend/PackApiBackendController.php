<?php

namespace App\Http\Controllers\ApiBackend;

use App\Models\Pack;
use Illuminate\Http\Request;

/**
 * Description of PackApiBackendController
 */
class PackApiBackendController extends \App\Http\Controllers\Controller
{
    public function show($pack)
    {
        $showExpired = request()->boolean('show_expired', false);

        $pack = Pack::find($pack);
        try {
            $pack->checkBrandOwnership();

            // Cargar las relaciones necesarias
            $pack->load([
                'rules' => function ($q) {
                    return $q->orderBy('id');
                },
                'sessions.event',
                'sessions.space.location',
            ]);

            // Obtener eventos a través del accessor
            $events = $pack->events;

            // 🔧 FIX: Asegurar que cada evento tenga sus sesiones correctamente
            if ($events && $events->count() > 0) {
                $events->each(function ($event) use ($showExpired) {

                    // Si show_expired=true usar todas las sessions, sino solo next_sessions
                    $sessionsToUse = $showExpired
                        ? $event->sessions
                        : $event->next_sessions;

                    // Copiar next_sessions a sessions para el frontend
                    // 🔧 FIX: Renombrar next_sessions a sessions para consistencia
                    $event->sessions = $sessionsToUse ?? [];

                    $event->sessions->each(function ($session) {
                        try {
                            $generalRate = $session->generalRate;
                            $price = $generalRate ? $generalRate->price : 0;

                            $session->setAttribute('price', $price);
                            $session->setAttribute('free_positions', $session->getFreePositions());

                            // Asegurar que space esté cargado
                            if (!$session->relationLoaded('space')) {
                                $session->load('space.location');
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error processing session {$session->id}: " . $e->getMessage());
                            $session->setAttribute('price', 0);
                            $session->setAttribute('free_positions', 0);
                        }
                    });
                });
            }

            // Estructurar la respuesta correctamente
            $response = [
                'id' => $pack->id,
                'name' => $pack->name,
                'description' => $pack->description,
                'starts_on' => $pack->starts_on,
                'ends_on' => $pack->ends_on,
                'min_per_cart' => $pack->min_per_cart,
                'max_per_cart' => $pack->max_per_cart,
                'round_to_nearest' => $pack->round_to_nearest,
                'one_session_x_event' => $pack->one_session_x_event,
                'is_all_sessions' => $pack->is_all_sessions,
                'rules' => $pack->rules,
                'events' => $events->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'name' => $event->name,
                        'sessions' => $event->sessions ?? $event->next_sessions ?? []
                    ];
                }),
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error("Error in PackApiBackendController@show for pack {$pack->id}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error loading pack data',
                'message' => $e->getMessage(),
                'pack_id' => $pack->id ?? 'unknown'
            ], 500);
        }
    }
}
