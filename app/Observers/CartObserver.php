<?php

namespace App\Observers;

use App\Models\Cart;
use App\Models\GroupPack;
use App\Services\RedisSlotsService;
use App\Services\InscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartObserver
{
    /**
     * Handle the Cart "updated" event.
     */
    public function updated(Cart $cart)
    {
        // Si cambió algo relevante, invalidar cache
        if ($cart->isDirty(['confirmation_code', 'expires_on'])) {
            app(InscriptionService::class)->invalidateCartCache($cart);

            // Si el carrito se confirmó, actualizar Redis
            if (!$cart->getOriginal('confirmation_code') && $cart->confirmation_code) {
                $this->confirmCartSlots($cart);
            }

            // Si cambió la expiración, actualizar SessionTempSlots
            if ($cart->isDirty('expires_on')) {
                $this->updateTempSlotsExpiration($cart);
            }
        }
    }

    /**
     * Handle the Cart "deleting" event (antes del soft delete)
     */
    public function deleting(Cart $cart)
    {
        // IMPORTANTE: Liberar slots ANTES del soft delete
        $this->releaseAllSlotsInRedis($cart);

        // Invalidar cache del carrito
        try {
            app(InscriptionService::class)->invalidateCartCache($cart);
        } catch (\Exception $e) {
            Log::error('CartObserver: Error invalidating cart cache', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Cart "deleted" event (después del soft delete)
     */
    public function deleted(Cart $cart)
    {
        // Añadir usuario que elimina
        if (auth()->user()) {
            DB::table('carts')
                ->where('id', $cart->id)
                ->update(['deleted_user_id' => auth()->user()->id]);
        }

        // Eliminar inscripciones (con sus slots ya liberados)
        $cart->inscriptions()->each(function ($inscription) {
            // Añadir usuario que elimina
            if (auth()->user()) {
                DB::table('inscriptions')
                    ->where('id', $inscription->id)
                    ->update(['deleted_user_id' => auth()->user()->id]);
            }

            // Eliminar inscripción
            $inscription->delete();
        });

        // Eliminar pagos
        $cart->payments()->each(function ($payment) {
            if (auth()->user()) {
                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update(['deleted_user_id' => auth()->user()->id]);
            }
            $payment->delete();
        });

        // Eliminar group packs y sus inscripciones
        $cart->groupPacks->each(function (GroupPack $groupPack) {
            // Liberar slots del pack
            foreach ($groupPack->inscriptions as $inscription) {
                if ($inscription->session_id && $inscription->slot_id) {
                    try {
                        $session = \App\Models\Session::find($inscription->session_id);
                        if ($session) {
                            $redisService = new RedisSlotsService($session);
                            $redisService->freeSlot($inscription->slot_id);
                        }
                    } catch (\Exception $e) {
                        Log::error('CartObserver: Error freeing pack slot', [
                            'slot_id' => $inscription->slot_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $inscription->delete();
            }

            // Eliminar PDF del pack
            $destination_path = 'pdf/packs';
            \Storage::disk()->delete("$destination_path/$groupPack->pdf");

            if (auth()->user()) {
                DB::table('group_packs')
                    ->where('id', $groupPack->id)
                    ->update(['deleted_user_id' => auth()->user()->id]);
            }

            $groupPack->delete();
        });

        // Limpiar SessionTempSlots
        \App\Models\SessionTempSlot::where('cart_id', $cart->id)->delete();
    }

    /**
     * Liberar TODOS los slots del carrito en Redis
     */
    private function releaseAllSlotsInRedis(Cart $cart): void
    {
        // Obtener TODAS las inscripciones (normales + packs)
        $allInscriptions = collect();

        // Inscripciones normales
        $normalInscriptions = $cart->inscriptions()->with('session')->get();
        $allInscriptions = $allInscriptions->merge($normalInscriptions);

        // Inscripciones de packs
        foreach ($cart->groupPacks as $groupPack) {
            $packInscriptions = $groupPack->inscriptions()->with('session')->get();
            $allInscriptions = $allInscriptions->merge($packInscriptions);
        }

        // Agrupar por sesión para optimizar
        $inscriptionsBySession = $allInscriptions->groupBy('session_id');

        foreach ($inscriptionsBySession as $sessionId => $inscriptions) {
            if (!$sessionId)
                continue;

            try {
                $session = \App\Models\Session::find($sessionId);
                if (!$session) {
                    Log::warning('CartObserver: Session not found', ['session_id' => $sessionId]);
                    continue;
                }

                $redisService = new RedisSlotsService($session);
                $freedCount = 0;

                // Liberar cada slot de esta sesión
                foreach ($inscriptions as $inscription) {
                    if ($inscription->slot_id) {
                        try {
                            $redisService->freeSlot($inscription->slot_id);
                            $freedCount++;

                            // También eliminar SessionSlot si existe
                            \App\Models\SessionSlot::where('session_id', $sessionId)
                                ->where('slot_id', $inscription->slot_id)
                                ->where('status_id', 2) // Status vendido
                                ->delete();
                        } catch (\Exception $e) {
                            Log::error('CartObserver: Error freeing individual slot', [
                                'slot_id' => $inscription->slot_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // Invalidar cache de disponibilidad
                if ($freedCount > 0) {
                    $redisService->invalidateAvailabilityCache();
                }
            } catch (\Exception $e) {
                Log::error('CartObserver: Error processing session', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // También limpiar SessionTempSlot por si acaso
        try {
            $tempSlots = \App\Models\SessionTempSlot::where('cart_id', $cart->id)->get();
            foreach ($tempSlots as $tempSlot) {
                if ($tempSlot->session_id && $tempSlot->slot_id) {
                    $session = \App\Models\Session::find($tempSlot->session_id);
                    if ($session) {
                        $redisService = new RedisSlotsService($session);
                        $redisService->freeSlot($tempSlot->slot_id);
                    }
                }
            }
            \App\Models\SessionTempSlot::where('cart_id', $cart->id)->delete();
        } catch (\Exception $e) {
            Log::error('CartObserver: Error cleaning temp slots', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Confirmar slots cuando se confirma el carrito
     */
    private function confirmCartSlots(Cart $cart): void
    {
        // Cuando un carrito se confirma, actualizar el estado de los slots
        $sessionIds = $cart->allInscriptions()
            ->pluck('session_id')
            ->unique()
            ->filter();

        foreach ($sessionIds as $sessionId) {
            try {
                $session = \App\Models\Session::find($sessionId);
                if (!$session) {
                    continue;
                }

                $redisService = new RedisSlotsService($session);

                // Obtener inscripciones de esta sesión
                $inscriptions = $cart->allInscriptions()
                    ->where('session_id', $sessionId);

                foreach ($inscriptions as $inscription) {
                    if ($inscription->slot_id) {
                        // Actualizar estado a confirmado
                        $redisService->updateSlotState($inscription->slot_id, [
                            'is_locked' => true,
                            'cart_id' => $cart->id,
                            'inscription_id' => $inscription->id,
                            'status' => 'confirmed',
                            'confirmation_code' => $cart->confirmation_code
                        ]);
                    }
                }

                // Invalidar cache
                $redisService->invalidateAvailabilityCache();
            } catch (\Exception $e) {
                Log::error('CartObserver: Error confirming slots in Redis', [
                    'cart_id' => $cart->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Actualizar expiración de slots temporales
     */
    private function updateTempSlotsExpiration(Cart $cart): void
    {
        try {
            // Actualizar todos los SessionTempSlot del carrito
            \App\Models\SessionTempSlot::where('cart_id', $cart->id)
                ->update(['expires_on' => $cart->expires_on]);
        } catch (\Exception $e) {
            Log::error('CartObserver: Error updating temp slots expiration', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
