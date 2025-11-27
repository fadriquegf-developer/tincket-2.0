<?php

namespace App\Observers;

use App\Models\Cart;
use App\Models\Inscription;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;
use App\Repositories\SessionRepository;
use App\Services\InscriptionService;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Log;

class InscriptionObserver
{
    /**
     * Se ejecuta antes de crear una inscripción
     * Asegura que el brand_id esté presente
     */
    public function creating(Inscription $inscription)
    {
        // Si no tiene brand_id pero tiene cart_id, obtenerlo del carrito
        if (!$inscription->brand_id && $inscription->cart_id) {
            $cart = Cart::find($inscription->cart_id);
            if ($cart && $cart->brand_id) {
                $inscription->brand_id = $cart->brand_id;
            } else {
                Log::warning("InscriptionObserver: No se pudo obtener brand_id del cart {$inscription->cart_id}");
            }
        }
    }

    /**
     * Se ejecuta al actualizar una inscripción
     * Mantiene consistencia del brand_id si cambia el cart_id
     */
    public function updating(Inscription $inscription)
    {
        // Si cambia el cart_id, actualizar brand_id
        if ($inscription->isDirty('cart_id') && $inscription->cart_id) {
            $cart = Cart::find($inscription->cart_id);
            if ($cart && $cart->brand_id) {
                $inscription->brand_id = $cart->brand_id;
            }
        }
    }

    /**
     * Se ejecuta después de crear una inscripción
     */
    /**
     * Se ejecuta después de crear una inscripción
     */
    public function created(Inscription $inscription)
    {
        // 🎯 NUEVO: Eliminar session_slot (reservas) al vender la butaca
        if ($inscription->slot_id && $inscription->session_id) {
            SessionSlot::where('session_id', $inscription->session_id)
                ->where('slot_id', $inscription->slot_id)
                ->whereIn('status_id', [3, 6, 7, 8]) // 3=Reservada VIP,6=Mobilitat reduida clicable, 7=Mobilitat reduida, 8=Reserva Abonament
                ->delete();
        }

        // Invalidar cache del precio del carrito
        if ($inscription->cart_id) {
            app(InscriptionService::class)->invalidateInscriptionCache($inscription);
        }

        // Invalidar cache de disponibilidad en Redis
        $this->invalidateRedisCache($inscription);

        // Invalidar cache del repository
        if ($inscription->session) {
            app(SessionRepository::class)->invalidateInscriptionCache($inscription->session);
        }
    }

    /**
     * Se ejecuta después de actualizar una inscripción
     */
    public function updated(Inscription $inscription)
    {
        // Invalidar cache si cambió el precio o cart_id
        if ($inscription->isDirty(['price_sold', 'cart_id', 'group_pack_id'])) {
            if ($inscription->cart_id) {
                app(InscriptionService::class)->invalidateInscriptionCache($inscription);
            }

            // Si cambió el cart_id, también invalidar el carrito anterior
            $oldCartId = $inscription->getOriginal('cart_id');
            if ($oldCartId && $oldCartId != $inscription->cart_id) {
                $oldCart = Cart::find($oldCartId);
                if ($oldCart) {
                    app(InscriptionService::class)->invalidateCartCache($oldCart);
                }
            }
        }

        // Invalidar cache de Redis si cambió el slot
        if ($inscription->isDirty('slot_id')) {
            $this->invalidateRedisCache($inscription);
        }

        // Si se validó la inscripción
        if ($inscription->isDirty('checked_at')) {
            if ($inscription->session) {
                app(SessionRepository::class)->invalidateValidationCache($inscription->session);
            }
        }
    }

    /**
     * Se ejecuta después de guardar (crear o actualizar)
     */
    public function saved(Inscription $inscription)
    {
        // Invalidar cache del precio del carrito
        if ($inscription->cart_id) {
            app(InscriptionService::class)->invalidateInscriptionCache($inscription);
        }

        // Gestionar slots con Redis
        $this->handleRedisSlotUpdate($inscription);

        // Crear stats si es necesario
        if (
            (!$inscription->getOriginal('pdf') && $inscription->pdf) ||
            ($inscription->getOriginal('deleted_at') && !$inscription->deleted_at)
        ) {
            \App\Models\StatsSales::createFromInscription($inscription)->save();
        }
    }

    /**
     * Se ejecuta al eliminar una inscripción
     */
    public function deleted(Inscription $inscription)
    {
        // Invalidar cache del carrito
        if ($inscription->cart_id) {
            app(InscriptionService::class)->invalidateInscriptionCache($inscription);
        }

        // Añadir usuario que elimina
        $user = auth()->user();
        if ($user) {
            \DB::table('inscriptions')
                ->where('id', $inscription->id)
                ->update(['deleted_user_id' => $user->id]);
        }

        // Eliminar stats
        \DB::table('stats_sales')->where('inscription_id', $inscription->id)->delete();

        // Liberar slot en Redis
        $this->handleRedisSlotDeletion($inscription);

        // Eliminar temp slots
        SessionTempSlot::where('inscription_id', $inscription->id)->delete();

        // Eliminar session slots vendidos
        if ($inscription->barcode && $inscription->session_id && $inscription->slot_id) {
            SessionSlot::where('session_id', $inscription->session_id)
                ->where('slot_id', $inscription->slot_id)
                ->where('status_id', 2)
                ->delete();
        }

        // Eliminar PDF
        $destinationPath = 'pdf/inscriptions';
        \Storage::disk()->delete("$destinationPath/$inscription->pdf");
    }

    /**
     * Maneja la actualización de slots en Redis
     */
    private function handleRedisSlotUpdate(Inscription $inscription): void
    {
        try {
            $session = $inscription->session;

            if (!$session || !$session->is_numbered) {
                return;
            }

            $redisService = new RedisSlotsService($session);

            // Liberar slot anterior si cambió
            $oldSlotId = $inscription->getOriginal('slot_id');
            if ($oldSlotId && $oldSlotId != $inscription->slot_id) {
                $redisService->freeSlot($oldSlotId);
            }

            // Bloquear nuevo slot si existe
            if ($inscription->slot_id) {
                $redisService->lockSlot(
                    $inscription->slot_id,
                    2, // Estado: vendido
                    null,
                    $inscription->cart_id
                );
            }

            // Invalidar cache de disponibilidad
            $redisService->invalidateAvailabilityCache();
        } catch (\Exception $e) {
            Log::error('InscriptionObserver: Error updating Redis slots', [
                'inscription_id' => $inscription->id,
                'old_slot_id' => $inscription->getOriginal('slot_id'),
                'new_slot_id' => $inscription->slot_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Maneja la eliminación de slots en Redis
     */
    private function handleRedisSlotDeletion(Inscription $inscription): void
    {
        try {
            if ($inscription->slot_id && $inscription->session) {
                $redisService = new RedisSlotsService($inscription->session);
                $redisService->freeSlot($inscription->slot_id);
                $redisService->invalidateAvailabilityCache();
            }
        } catch (\Exception $e) {
            Log::error('InscriptionObserver: Error freeing slot in Redis', [
                'inscription_id' => $inscription->id,
                'slot_id' => $inscription->slot_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalida la cache de Redis para la sesión
     */
    private function invalidateRedisCache(Inscription $inscription): void
    {
        try {
            if ($inscription->session_id && $inscription->session) {
                $redisService = new RedisSlotsService($inscription->session);
                $redisService->invalidateAvailabilityCache();
            }
        } catch (\Exception $e) {
            Log::error('InscriptionObserver: Error invalidating Redis cache', [
                'inscription_id' => $inscription->id,
                'session_id' => $inscription->session_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
