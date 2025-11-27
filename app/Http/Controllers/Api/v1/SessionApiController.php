<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Session;
use App\Scopes\BrandScope;
use App\Http\Resources\SessionShowResource;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionApiController extends \App\Http\Controllers\Api\ApiController
{
    /**
     * Display the specified resource.
     */
    public function show(int $session_id)
    {
        $brand = request()->get('brand');
        $showExpired = request()->get('show_expired', false);

        $allowedBrandIds = array_merge(
            [$brand->id],
            $brand->children->pluck('id')->toArray()
        );

        $builder = Session::withoutGlobalScope(BrandScope::class)
            ->with([
                'brand.capability',
                'space' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'location' => function ($locQ) {
                                $locQ->withoutGlobalScope(BrandScope::class);
                            }
                        ]);
                },
                'event' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class);
                },
                'allRates' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class)
                        ->with([
                            'rate' => function ($rateQ) {
                                $rateQ->withoutGlobalScope(BrandScope::class)
                                    ->with([  // ✨ AÑADIR AQUÍ
                                        'form' => function ($fq) {
                                            $fq->withoutGlobalScope(BrandScope::class)
                                                ->with([
                                                    'form_fields' => function ($ffq) {
                                                        $ffq->withoutGlobalScope(BrandScope::class);
                                                    }
                                                ]);
                                        }
                                    ]);
                            }
                        ])
                        ->where('is_public', true);
                }
            ])
            ->where('id', $session_id)
            ->whereIn('brand_id', $allowedBrandIds);

        // El show_expired debería solo afectar a 'ends_on', no a las fechas de inscripción
        $builder->where('inscription_starts_on', '<=', now())
            ->where('inscription_ends_on', '>=', now());

        if (!$showExpired) {
            $builder->where('ends_on', '>', now())
                ->where(function ($query) {
                    $query->where('visibility', 1)
                        ->orWhere('private', 1);
                });
        }

        $session = $builder->firstOrFail();

        if (!in_array($session->brand_id, $allowedBrandIds)) {
            abort(404, 'Session not found or access denied');
        }

        $cacheKey = "session:{$session_id}:web_positions";
        $freePositions = Cache::remember($cacheKey, 30, function () use ($session) {
            return $session->getAvailableWebPositions();
        });

        $session->setAttribute('count_free_positions', $freePositions);

        Cache::forget("session_{$session_id}_public_rates_formatted");

        $publicRates = $session->public_rates;

        $session->setAttribute('rates', $publicRates->toArray());

        return new SessionShowResource($session);
    }

    /**
     * Obtener configuración de la sesión (slots, zonas, etc)
     */
    public function configuration(int $session_id)
    {
        try {
            $brand = request()->get('brand');
            $allowedBrandIds = array_merge(
                [$brand->id],
                $brand->children->pluck('id')->toArray()
            );

            // Buscar sesión sin BrandScope
            $session = Session::withoutGlobalScope(BrandScope::class)
                ->whereIn('brand_id', $allowedBrandIds)
                ->findOrFail($session_id);

            // Verificar acceso
            if (!in_array($session->brand_id, $allowedBrandIds)) {
                abort(404, 'Session not found or access denied');
            }

            // Activar rates privadas antes de obtener configuración
            $redisService = RedisSlotsService::for($session);

            $configuration = $redisService->getConfiguration();

            // Agregar información adicional del space
            if ($session->space) {
                $configuration['space_details'] = [
                    'name' => $session->space->name ?? '',
                    'description' => $session->space->description ?? '',
                    'zoom' => $session->space->zoom ?? false,
                    'svg_path' => $session->space->svg_host_path ?? null,
                    'capacity' => $session->space->capacity ?? 0
                ];
            }

            // Obtener estadísticas de performance
            $perfStats = $redisService->getPerformanceStats();

            return response()->json([
                'success' => true,
                'data' => $configuration,
                'metadata' => [
                    'session' => [
                        'id' => $session->id,
                        'name' => $session->name,
                        'starts_on' => $session->starts_on->toIso8601String(),
                        'ends_on' => $session->ends_on->toIso8601String()
                    ],
                    'cache' => [
                        'source' => 'redis',
                        'cached_at' => $configuration['cached_at'] ?? now()->toIso8601String(),
                        'version' => $configuration['version'] ?? 'v2',
                        'build_time_ms' => $perfStats['build_time_ms'] ?? null,
                        'age_seconds' => $perfStats['age_seconds'] ?? null,
                        'stale' => $perfStats['stale'] ?? false
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting session configuration", [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading configuration',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener disponibilidad de slots específicos
     */
    public function checkAvailability(int $session_id)
    {
        $request = request();

        try {
            $brand = $request->get('brand');
            $allowedBrandIds = array_merge(
                [$brand->id],
                $brand->children->pluck('id')->toArray()
            );

            $session = Session::withoutGlobalScope(BrandScope::class)
                ->whereIn('brand_id', $allowedBrandIds)
                ->findOrFail($session_id);

            if (!in_array($session->brand_id, $allowedBrandIds)) {
                abort(404, 'Session not found or access denied');
            }

            $slotIds = $request->input('slot_ids', []);
            $isTicketOffice = $request->input('is_ticket_office', false);
            $isForPack = $request->input('is_for_pack', false);

            if (empty($slotIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No slot IDs provided'
                ], 400);
            }

            $redisService = RedisSlotsService::for($session);
            $availability = $redisService->checkBulkAvailability(
                $slotIds,
                $isTicketOffice,
                $isForPack
            );

            return response()->json([
                'success' => true,
                'data' => $availability,
                'metadata' => [
                    'total_checked' => count($slotIds),
                    'total_available' => collect($availability)->filter()->count(),
                    'checked_at' => now()->toIso8601String()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error checking availability", [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking availability'
            ], 500);
        }
    }

    /**
     * Forzar regeneración de cache para una sesión
     */
    public function regenerateCache(int $session_id)
    {
        try {
            $session = Session::findOrFail($session_id);

            // Verificar permisos (solo admin o owner)
            if (!$this->canManageSession($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $redisService = RedisSlotsService::for($session);

            // Limpiar cache existente
            $redisService->clearAllCache();

            // Regenerar
            $redisService->regenerateConfiguration();

            return response()->json([
                'success' => true,
                'message' => 'Cache regenerated successfully',
                'session_id' => $session_id,
                'regenerated_at' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error("Error regenerating cache", [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate cache'
            ], 500);
        }
    }

    /**
     * Limpiar slots expirados
     */
    public function cleanExpiredSlots(int $session_id)
    {
        try {
            $session = Session::findOrFail($session_id);

            if (!$this->canManageSession($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $redisService = RedisSlotsService::for($session);
            $freedCount = $redisService->freeExpiredSlots();

            return response()->json([
                'success' => true,
                'freed_slots' => $freedCount,
                'session_id' => $session_id,
                'cleaned_at' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            Log::error("Error cleaning expired slots", [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clean expired slots'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de performance de la sesión
     */
    public function performanceStats(int $session_id)
    {
        try {
            $session = Session::findOrFail($session_id);

            if (!$this->canManageSession($session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $redisService = RedisSlotsService::for($session);
            $stats = $redisService->getPerformanceStats();

            // Agregar estadísticas adicionales
            $stats['database'] = [
                'total_inscriptions' => $session->inscriptions()->count(),
                'confirmed_inscriptions' => $session->inscriptions()
                    ->whereHas('cart', function ($q) {
                        $q->whereNotNull('confirmation_code');
                    })->count(),
                'total_slots' => $session->space ? $session->space->slots()->count() : 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'session_id' => $session_id
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting performance stats", [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance stats'
            ], 500);
        }
    }

    /**
     * Verificar si el usuario puede gestionar la sesión
     */
    private function canManageSession(Session $session): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Admin siempre puede
        if ($user->hasRole('admin')) {
            return true;
        }

        // Owner de la sesión
        if ($session->user_id === $user->id) {
            return true;
        }

        // Brand admin
        if ($user->brands->contains($session->brand_id)) {
            return true;
        }

        return false;
    }
}
