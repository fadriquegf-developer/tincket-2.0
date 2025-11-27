<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Slot;
use App\Models\Cart;
use App\Models\Inscription;
use App\Models\SessionTempSlot;
use App\Models\AssignatedRate;
use App\Exceptions\SlotNotAvailableException;
use App\Exceptions\SessionFullException;
use App\Services\RedisSlotsService;
use App\Services\RedisDistributedLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar inscripciones con protección contra race conditions
 * Implementa lock distribuido para garantizar atomicidad
 */
class InscriptionService
{

    private RedisSlotsService $redisService;
    /**
     * Reservar un slot con lock distribuido y bloqueo pesimista
     * Garantiza atomicidad completa en la reserva
     */
    public function reserveSlot(
        Session $session,
        Slot $slot,
        Cart $cart,
        int $rateId,
        array $additionalData = [],
        bool $isTicketOffice = false
    ): Inscription {
        $this->redisService = new RedisSlotsService($session);

        // Crear lock distribuido para este slot específico
        $lockKey = "session:{$session->id}:slot:{$slot->id}";
        $lock = new RedisDistributedLock($lockKey, ttl: 15, retryDelay: 50, maxRetries: 40);

        try {
            // PASO 1: Adquirir lock distribuido (Redis)
            if (!$lock->acquire()) {
                Log::warning('Could not acquire distributed lock for slot', [
                    'session_id' => $session->id,
                    'slot_id' => $slot->id,
                    'cart_id' => $cart->id
                ]);
                throw new SlotNotAvailableException('El slot está siendo procesado por otra operación');
            }

            // PASO 2: Ejecutar transacción con el lock distribuido activo
            $inscription = DB::transaction(function () use ($session, $slot, $cart, $rateId, $additionalData, $lock, $isTicketOffice) {

                // 2.1: Verificar disponibilidad en Redis PRIMERO (con lock activo) taquilla pot
                if (!$this->redisService->isSlotAvailable($slot->id, $isTicketOffice)) {
                    throw new SlotNotAvailableException('El slot ya no está disponible');
                }

                // 2.2: Bloquear el slot en DB para actualizacion (PESSIMISTIC LOCKING)
                $lockedSlot = Slot::where('id', $slot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedSlot) {
                    throw new SlotNotAvailableException('Slot no encontrado');
                }

                // 2.3: Doble verificación de disponibilidad con el slot bloqueado en DB
                if (!$this->isSlotReallyAvailable($session, $lockedSlot, $cart, $isTicketOffice)) {
                    throw new SlotNotAvailableException('El slot ya está reservado');
                }

                // 2.4: Verificar capacidad de la sesión
                $this->checkSessionCapacity($session);

                // 2.5: Marcar inmediatamente como NO disponible en Redis
                $this->redisService->lockSlot(
                    $slot->id,
                    1, // Estado: reservando
                    null,
                    $cart->id
                );

                // 2.6: Obtener el precio de la tarifa
                $price = $this->getRatePrice($session, $slot, $rateId);

                // 2.7: Crear inscripción en DB
                $inscription = Inscription::create([
                    'session_id' => $session->id,
                    'slot_id' => $slot->id,
                    'cart_id' => $cart->id,
                    'rate_id' => $rateId,
                    'brand_id' => $session->brand_id,
                    'code' => $additionalData['code'] ?? null,
                    'metadata' => $additionalData['metadata'] ?? null,
                    'price' => $price,
                    'price_sold' => $price,
                    'barcode' => $this->generateUniqueBarcode(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // 2.8: Crear bloqueo temporal si el carrito no está confirmado
                if (!$cart->confirmation_code) {
                    SessionTempSlot::create([
                        'session_id' => $session->id,
                        'slot_id' => $slot->id,
                        'inscription_id' => $inscription->id,
                        'cart_id' => $cart->id,
                        'status_id' => 2, // Bloqueado temporalmente
                        'expires_on' => $cart->expires_on
                    ]);
                }

                // 2.9: Actualizar estado final en Redis
                $this->redisService->lockSlot(
                    $slot->id,
                    2, // Estado: reservado
                    $inscription->id,
                    $cart->id
                );

                // 2.10: Extender lock si la operación tomó mucho tiempo
                if (!$lock->extend(5)) {
                    Log::warning('Could not extend lock, but inscription was created', [
                        'inscription_id' => $inscription->id
                    ]);
                }

                return $inscription;
            }, 3); // Reintentar hasta 3 veces en caso de deadlock

            // Invalidar cache después de reserva exitosa
            $this->invalidateSessionCache($session);

            return $inscription;
        } catch (\Exception $e) {
            // Si algo falla, asegurar que Redis quede consistente
            try {
                $this->redisService->freeSlot($slot->id);
                $this->invalidateSessionCache($session);
            } catch (\Exception $redisError) {
                Log::error('Failed to rollback Redis slot on error', [
                    'slot_id' => $slot->id,
                    'original_error' => $e->getMessage(),
                    'redis_error' => $redisError->getMessage()
                ]);
            }

            throw $e;
        } finally {
            // SIEMPRE liberar el lock distribuido
            $lock->release();
        }
    }

    /**
     * Reservar múltiples slots de forma atómica
     * Usa un solo lock para toda la operación
     */
    public function reserveMultipleSlots(
        Session $session,
        array $slotIds,
        Cart $cart,
        int $rateId,
        bool $isTicketOffice = false
    ): array {
        $this->redisService = new RedisSlotsService($session);

        // Lock a nivel de sesión para operación múltiple
        $lockKey = "session:{$session->id}:bulk:cart:{$cart->id}";
        $lock = new RedisDistributedLock($lockKey, ttl: 30, retryDelay: 100, maxRetries: 20);

        $reservedSlots = [];
        $inscriptions = [];

        try {
            if (!$lock->acquire()) {
                throw new SlotNotAvailableException('No se pudo procesar la reserva múltiple');
            }

            $inscriptions = DB::transaction(function () use ($session, $slotIds, $cart, $rateId, &$reservedSlots, $isTicketOffice) {
                $inscriptions = [];

                // Verificar disponibilidad de TODOS los slots primero
                foreach ($slotIds as $slotId) {
                    if (!$this->redisService->isSlotAvailable($slotId, $isTicketOffice)) {
                        throw new SlotNotAvailableException("El slot {$slotId} no está disponible");
                    }
                }

                // Bloquear todos los slots en DB
                $slots = Slot::whereIn('id', $slotIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($slots->count() !== count($slotIds)) {
                    throw new SlotNotAvailableException('Algunos slots no existen');
                }

                // Verificar capacidad total
                $this->checkSessionCapacity($session, count($slotIds));

                // Marcar todos como reservando en Redis
                foreach ($slotIds as $slotId) {
                    $this->redisService->lockSlot($slotId, 1, null, $cart->id);
                    $reservedSlots[] = $slotId;
                }

                // Crear todas las inscripciones
                foreach ($slotIds as $slotId) {
                    $slot = $slots[$slotId];
                    $price = $this->getRatePrice($session, $slot, $rateId);

                    $inscription = Inscription::create([
                        'session_id' => $session->id,
                        'slot_id' => $slotId,
                        'cart_id' => $cart->id,
                        'rate_id' => $rateId,
                        'brand_id' => $session->brand_id,
                        'price' => $price,
                        'price_sold' => $price,
                        'barcode' => $this->generateUniqueBarcode(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Crear bloqueo temporal
                    if (!$cart->confirmation_code) {
                        SessionTempSlot::create([
                            'session_id' => $session->id,
                            'slot_id' => $slotId,
                            'inscription_id' => $inscription->id,
                            'cart_id' => $cart->id,
                            'status_id' => 2,
                            'expires_on' => $cart->expires_on
                        ]);
                    }

                    // Actualizar estado final en Redis
                    $this->redisService->lockSlot($slotId, 2, $inscription->id, $cart->id);

                    $inscriptions[] = $inscription;
                }

                return $inscriptions;
            }, 3);

            $this->invalidateSessionCache($session);
            return $inscriptions;
        } catch (\Exception $e) {
            // Rollback en Redis
            foreach ($reservedSlots as $slotId) {
                try {
                    $this->redisService->freeSlot($slotId);
                } catch (\Exception $redisError) {
                    Log::error('Failed to rollback Redis slot', [
                        'slot_id' => $slotId,
                        'error' => $redisError->getMessage()
                    ]);
                }
            }

            $this->invalidateSessionCache($session);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Liberar un slot (cancelar inscripción) con lock distribuido
     */
    public function releaseSlot(Inscription $inscription): bool
    {
        $session = $inscription->session;
        $this->redisService = new RedisSlotsService($session);

        $lockKey = "session:{$session->id}:slot:{$inscription->slot_id}";
        $lock = new RedisDistributedLock($lockKey, ttl: 10);

        try {
            if (!$lock->acquire()) {
                throw new \Exception('No se pudo adquirir lock para liberar slot');
            }

            return DB::transaction(function () use ($inscription) {
                // Bloquear slot
                $slot = Slot::where('id', $inscription->slot_id)
                    ->lockForUpdate()
                    ->first();

                if (!$slot) {
                    return false;
                }

                // Eliminar inscripción
                $inscription->delete();

                // Eliminar bloqueo temporal
                SessionTempSlot::where('inscription_id', $inscription->id)->delete();

                // Liberar en Redis
                $this->redisService->freeSlot($inscription->slot_id);

                // Invalidar cache
                $this->invalidateSessionCache($inscription->session);

                return true;
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Verificar disponibilidad real del slot
     * Consulta DB y Redis para garantizar consistencia
     */
    private function isSlotReallyAvailable(Session $session, Slot $slot, Cart $cart, bool $isTicketOffice = false): bool
    {
        // Verificar en DB
        $existingInscription = Inscription::where('session_id', $session->id)
            ->where('slot_id', $slot->id)
            ->whereHas('cart', function ($q) {
                $q->whereNotNull('confirmation_code')
                    ->orWhere('expires_on', '>', now());
            })
            ->where('cart_id', '!=', $cart->id)
            ->exists();

        if ($existingInscription) {
            return false;
        }

        // Verificar bloqueos temporales
        $tempSlot = SessionTempSlot::where('session_id', $session->id)
            ->where('slot_id', $slot->id)
            ->where('cart_id', '!=', $cart->id)
            ->where('expires_on', '>', now())
            ->exists();

        if ($tempSlot) {
            return false;
        }

        // Verificar en Redis (doble check)
        return $this->redisService->isSlotAvailable($slot->id, $isTicketOffice);
    }

    /**
     * Verificar capacidad de la sesión
     */
    private function checkSessionCapacity(Session $session, int $additionalSlots = 1): void
    {
        // Usar max_places si está definido, sino usar capacity del space
        $maxCapacity = $session->max_places ?? $session->space->capacity ?? 100;

        $currentOccupancy = Inscription::where('session_id', $session->id)
            ->whereHas('cart', function ($q) {
                $q->whereNotNull('confirmation_code')
                    ->orWhere('expires_on', '>', now());
            })
            ->count();

        $availableCapacity = $maxCapacity - $currentOccupancy;

        if ($availableCapacity < $additionalSlots) {
            throw new SessionFullException(
                "Sesión llena. Disponibles: {$availableCapacity}, Solicitadas: {$additionalSlots}"
            );
        }
    }

    /**
     * Obtener precio de la tarifa
     */
    private function getRatePrice(Session $session, Slot $slot, int $rateId): float
    {
        // Buscar tarifa asignada
        $assignedRate = AssignatedRate::where('session_id', $session->id)
            ->where('rate_id', $rateId)
            ->first();

        if (!$assignedRate) {
            throw new \Exception("Tarifa no válida para esta sesión");
        }

        // Si la sesión tiene zonas, verificar precio por zona
        if ($session->has_zones && $slot->zone_id) {
            $zoneRate = AssignatedRate::where('session_id', $session->id)
                ->where('rate_id', $rateId)
                ->where('zone_id', $slot->zone_id)
                ->first();

            if ($zoneRate) {
                return $zoneRate->price;
            }
        }

        return $assignedRate->price;
    }

    /**
     * Generar código de barras único
     */
    private function generateUniqueBarcode(): string
    {
        do {
            $barcode = strtoupper(bin2hex(random_bytes(6)));
        } while (Inscription::where('barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Invalidar cache de la sesión
     */
    private function invalidateSessionCache(Session $session): void
    {
        try {
            $redisService = new RedisSlotsService($session);
            $redisService->invalidateAvailabilityCache();

            // Invalidar también cache de Laravel
            \Cache::tags(["session:{$session->id}"])->flush();
        } catch (\Exception $e) {
            Log::warning('Could not invalidate session cache', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calcular precio total del carrito
     */
    public function calculateCartPrice(Cart $cart): float
    {
        return $cart->allInscriptions()->sum('price_sold') ?? 0.00;
    }

    /**
     * Invalidar cache de inscripción
     */
    public function invalidateInscriptionCache(Inscription $inscription): void
    {
        $cacheKey = "cart_price_{$inscription->cart_id}";
        \Cache::forget($cacheKey);

        if ($inscription->session_id && $inscription->session) {
            $this->invalidateSessionCache($inscription->session);
        }
    }

    /**
     * Invalidar cache del carrito
     */
    public function invalidateCartCache(Cart $cart): void
    {
        $cacheKey = "cart_price_{$cart->id}";
        \Cache::forget($cacheKey);

        $sessionIds = $cart->allInscriptions()
            ->pluck('session_id')
            ->unique()
            ->filter();

        foreach ($sessionIds as $sessionId) {
            $session = Session::find($sessionId);
            if ($session) {
                $this->invalidateSessionCache($session);
            }
        }
    }
}
