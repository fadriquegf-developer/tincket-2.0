<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Session;
use App\Models\Inscription;
use App\Models\SessionTempSlot;
use App\Exceptions\SlotNotAvailableException;
use App\Services\RedisSlotsService;
use App\Services\RedisDistributedLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * PaymentSlotLockService
 * ════════════════════════════════════════════════════════════════════════════
 * 
 * PROPÓSITO:
 * ----------
 * Este servicio resuelve el problema de race condition donde dos usuarios
 * pueden pagar por el mismo asiento si uno tarda mucho en completar el pago.
 * 
 * EL PROBLEMA QUE RESUELVE:
 * -------------------------
 * 1. Usuario A añade butaca → Lock de 15 min
 * 2. Usuario A va a pagar, tarda 16 minutos en Redsys (3D Secure lento)
 * 3. El carrito de A expira → Lock se libera
 * 4. Usuario B añade la MISMA butaca → Funciona porque está libre
 * 5. Usuario B paga rápido → Se confirma
 * 6. Usuario A termina de pagar → También se confirma (¡DUPLICADO!)
 * 
 * LA SOLUCIÓN:
 * ------------
 * 1. Cuando el usuario va a pagar, marcamos los slots como "IN_PAYMENT"
 * 2. Este estado tiene TTL de 10 minutos y NO se libera aunque el carrito expire
 * 3. Si otro usuario intenta añadir ese slot, ve "asiento en proceso de pago"
 * 4. En el callback de Redsys, verificamos con lock atómico antes de confirmar
 * 
 * ════════════════════════════════════════════════════════════════════════════
 */
class PaymentSlotLockService
{
    /**
     * TTL del lock de pago en segundos (10 minutos)
     * 
     * ¿Por qué 10 minutos?
     * - El usuario promedio tarda 2-3 minutos en pagar
     * - Con 3D Secure puede tardar hasta 5-7 minutos
     * - Dejamos margen de seguridad
     * - Si tarda más de 10 min, probablemente abandonó el pago
     */
    private const PAYMENT_LOCK_TTL = 600;

    /**
     * Prefijo para las keys de Redis que marcan slots "en proceso de pago"
     * 
     * Formato final: "in_payment:s{session_id}:slot{slot_id}"
     * Ejemplo: "in_payment:s123:slot456"
     */
    private const PAYMENT_LOCK_PREFIX = 'in_payment:';

    /**
     * Minutos extra para extender el carrito cuando inicia el pago
     */
    private const CART_EXTENSION_MINUTES = 15;

    /**
     * ════════════════════════════════════════════════════════════════════════
     * MÉTODO PRINCIPAL 1: lockSlotsForPayment
     * ════════════════════════════════════════════════════════════════════════
     * 
     * CUÁNDO SE LLAMA:
     * ----------------
     * Se llama desde CartApiController::getPayment() ANTES de redirigir
     * al usuario a la pasarela de pago (Redsys).
     * 
     * QUÉ HACE:
     * ---------
     * 1. Obtiene todos los slots del carrito (inscripciones con slot_id)
     * 2. Verifica que cada slot sigue disponible (no vendido a otro)
     * 3. Verifica que cada slot no está siendo pagado por otro carrito
     * 4. Marca cada slot como "in_payment" en Redis
     * 5. Extiende la fecha de expiración del carrito
     * 
     * SI FALLA:
     * ---------
     * Lanza SlotNotAvailableException con detalles de qué slots fallaron.
     * El controlador debe capturar esto y devolver error 409 al frontend.
     * 
     * @param Cart $cart El carrito que va a iniciar el pago
     * @throws SlotNotAvailableException Si algún slot no está disponible
     * @return array Información del bloqueo realizado
     */
    public function lockSlotsForPayment(Cart $cart): array
    {
        // ─────────────────────────────────────────────────────────────────
        // PASO 1: Obtener todas las inscripciones con slot del carrito
        // ─────────────────────────────────────────────────────────────────
        // Solo nos interesan las inscripciones que tienen un slot_id asignado
        // (las inscripciones de sesiones no numeradas no tienen slot_id)

        $inscriptions = $cart->inscriptions()
            ->whereNotNull('slot_id')
            ->with(['session', 'slot'])
            ->get();

        // Si no hay inscripciones con slots, no hay nada que bloquear
        if ($inscriptions->isEmpty()) {
            return [
                'success' => true,
                'locked_slots' => [],
                'message' => 'No numbered slots to lock'
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // PASO 2: Verificar disponibilidad de todos los slots
        // ─────────────────────────────────────────────────────────────────
        // Antes de bloquear, verificamos que todos los slots siguen siendo
        // válidos para este carrito. Un slot NO está disponible si:
        // 
        // a) Ya fue vendido (existe otro carrito CONFIRMADO con ese slot)
        // b) Está siendo pagado por otro carrito (key "in_payment" en Redis)
        // c) Está bloqueado temporalmente por otro carrito NO expirado

        $conflicts = [];
        $slotsToLock = [];

        foreach ($inscriptions as $inscription) {
            $slotId = $inscription->slot_id;
            $sessionId = $inscription->session_id;
            $session = $inscription->session;

            // Verificación a): ¿Ya fue vendido a otro?
            $existingSale = $this->checkIfSlotSoldToAnother($slotId, $sessionId, $cart->id);

            if ($existingSale) {
                $conflicts[] = [
                    'slot_id' => $slotId,
                    'slot_name' => $inscription->slot->name ?? "Slot #{$slotId}",
                    'session_id' => $sessionId,
                    'reason' => 'already_sold',
                    'existing_cart_id' => $existingSale->cart_id,
                    'existing_confirmation_code' => $existingSale->cart->confirmation_code ?? null
                ];
                continue;
            }

            // Verificación b): ¿Está siendo pagado por otro?
            $paymentLock = $this->checkIfSlotInPayment($sessionId, $slotId, $cart->id);

            if ($paymentLock) {
                $conflicts[] = [
                    'slot_id' => $slotId,
                    'slot_name' => $inscription->slot->name ?? "Slot #{$slotId}",
                    'session_id' => $sessionId,
                    'reason' => 'in_payment_by_another',
                    'other_cart_id' => $paymentLock['cart_id']
                ];
                continue;
            }

            // Verificación c): ¿Bloqueado por otro carrito no expirado?
            $tempBlock = $this->checkIfSlotBlockedByAnother($slotId, $sessionId, $cart->id);

            if ($tempBlock) {
                $conflicts[] = [
                    'slot_id' => $slotId,
                    'slot_name' => $inscription->slot->name ?? "Slot #{$slotId}",
                    'session_id' => $sessionId,
                    'reason' => 'blocked_by_another_cart',
                    'other_cart_id' => $tempBlock->cart_id,
                    'expires_on' => $tempBlock->expires_on
                ];
                continue;
            }

            // ✅ Este slot está disponible para bloquear
            $slotsToLock[] = [
                'slot_id' => $slotId,
                'session_id' => $sessionId,
                'inscription_id' => $inscription->id
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // PASO 3: Si hay conflictos, lanzar excepción
        // ─────────────────────────────────────────────────────────────────
        // No bloqueamos parcialmente. O todos los slots están disponibles,
        // o ninguno se bloquea.

        if (!empty($conflicts)) {
            Log::warning('PaymentSlotLock: Conflicts detected, cannot lock', [
                'cart_id' => $cart->id,
                'conflicts' => $conflicts
            ]);

            throw new SlotNotAvailableException(
                'Algunos asientos ya no están disponibles',
                $conflicts
            );
        }

        // ─────────────────────────────────────────────────────────────────
        // PASO 4: Bloquear todos los slots en Redis
        // ─────────────────────────────────────────────────────────────────
        // Marcamos cada slot con una key especial "in_payment:..." que
        // tiene TTL de 10 minutos. Esta key es independiente del carrito.

        $lockedSlots = [];

        foreach ($slotsToLock as $slotInfo) {
            $this->markSlotInPayment(
                $slotInfo['session_id'],
                $slotInfo['slot_id'],
                $cart->id
            );
            $lockedSlots[] = $slotInfo;
        }

        // ─────────────────────────────────────────────────────────────────
        // PASO 5: Extender la expiración del carrito
        // ─────────────────────────────────────────────────────────────────
        // Esto es CRÍTICO. Si el usuario tarda en pagar, no queremos que
        // el carrito expire mientras está en Redsys.
        // 
        // Usamos max() para no acortar si el carrito ya tiene más tiempo.

        $newExpiration = max(
            $cart->expires_on,
            now()->addMinutes(self::CART_EXTENSION_MINUTES)
        );

        $cart->expires_on = $newExpiration;
        $cart->save();

        // También extender los SessionTempSlot asociados
        SessionTempSlot::where('cart_id', $cart->id)
            ->update(['expires_on' => $newExpiration]);

        // ─────────────────────────────────────────────────────────────────
        // PASO 6: Log y retorno
        // ─────────────────────────────────────────────────────────────────

        Log::info('PaymentSlotLock: Slots locked for payment', [
            'cart_id' => $cart->id,
            'locked_count' => count($lockedSlots),
            'locked_slots' => array_column($lockedSlots, 'slot_id'),
            'cart_expires_on' => $newExpiration->toIso8601String()
        ]);

        return [
            'success' => true,
            'locked_slots' => $lockedSlots,
            'cart_expires_on' => $newExpiration->toIso8601String()
        ];
    }

    /**
     * ════════════════════════════════════════════════════════════════════════
     * MÉTODO PRINCIPAL 2: verifyAndConfirmSlots
     * ════════════════════════════════════════════════════════════════════════
     * 
     * CUÁNDO SE LLAMA:
     * ----------------
     * Se llama desde AbstractPaymentService::confirmPayment() cuando Redsys
     * nos notifica que el pago fue exitoso.
     * 
     * QUÉ HACE:
     * ---------
     * 1. Adquiere un lock distribuido para evitar callbacks concurrentes
     * 2. Verifica que ningún slot fue vendido mientras el usuario pagaba
     * 3. Si todo OK, retorna success = true
     * 4. Si hay conflictos, retorna success = false con detalles
     * 
     * ¿POR QUÉ NECESITAMOS ESTO SI YA BLOQUEAMOS EN lockSlotsForPayment?
     * ------------------------------------------------------------------
     * Porque el lock de "in_payment" puede haber expirado (10 min) o porque
     * pueden llegar dos callbacks de Redsys casi simultáneamente para el
     * mismo carrito (retry de notificación).
     * 
     * Esta es la ÚLTIMA LÍNEA DE DEFENSA antes de confirmar el carrito.
     * 
     * @param Cart $cart El carrito cuyo pago se completó
     * @return array ['success' => bool, 'conflicts' => array]
     */
    public function verifyAndConfirmSlots(Cart $cart): array
    {
        // ─────────────────────────────────────────────────────────────────
        // PASO 1: Obtener inscripciones con slots
        // ─────────────────────────────────────────────────────────────────

        $inscriptions = $cart->inscriptions()
            ->whereNotNull('slot_id')
            ->with(['session', 'slot'])
            ->get();

        if ($inscriptions->isEmpty()) {
            return [
                'success' => true,
                'conflicts' => [],
                'message' => 'No numbered slots to verify'
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // PASO 2: Crear lock distribuido a nivel de carrito
        // ─────────────────────────────────────────────────────────────────
        // Este lock evita que dos callbacks del mismo carrito se procesen
        // simultáneamente (Redsys puede enviar múltiples notificaciones)

        $lockKey = "payment_confirm:cart:{$cart->id}";
        $lock = new RedisDistributedLock($lockKey, ttl: 30, retryDelay: 100, maxRetries: 50);

        if (!$lock->acquire()) {
            Log::warning('PaymentSlotLock: Could not acquire confirmation lock', [
                'cart_id' => $cart->id
            ]);

            // Si no podemos adquirir el lock, otro proceso está confirmando
            // Retornamos "already_processing" para que el caller espere/retry
            return [
                'success' => false,
                'conflicts' => [],
                'reason' => 'already_processing',
                'message' => 'Another process is confirming this cart'
            ];
        }

        try {
            // ─────────────────────────────────────────────────────────────
            // PASO 3: Verificar cada slot dentro del lock
            // ─────────────────────────────────────────────────────────────

            $conflicts = [];

            foreach ($inscriptions as $inscription) {
                $slotId = $inscription->slot_id;
                $sessionId = $inscription->session_id;

                // Buscar si existe otro carrito CONFIRMADO con este slot
                $existingSale = $this->checkIfSlotSoldToAnother(
                    $slotId,
                    $sessionId,
                    $cart->id
                );

                if ($existingSale) {
                    $conflicts[] = [
                        'slot_id' => $slotId,
                        'slot_name' => $inscription->slot->name ?? "Slot #{$slotId}",
                        'session_id' => $sessionId,
                        'reason' => 'already_sold',
                        'existing_cart_id' => $existingSale->cart_id,
                        'existing_confirmation_code' => $existingSale->cart->confirmation_code ?? null
                    ];
                }
            }

            // ─────────────────────────────────────────────────────────────
            // PASO 4: Retornar resultado
            // ─────────────────────────────────────────────────────────────

            if (!empty($conflicts)) {
                Log::error('PaymentSlotLock: Duplicate slots detected during confirmation', [
                    'cart_id' => $cart->id,
                    'conflicts' => $conflicts
                ]);

                return [
                    'success' => false,
                    'conflicts' => $conflicts,
                    'reason' => 'duplicate_slots',
                    'message' => 'Some slots were sold to another customer'
                ];
            }

            // Todo OK, los slots son válidos
            Log::info('PaymentSlotLock: Slots verified successfully', [
                'cart_id' => $cart->id,
                'verified_slots' => $inscriptions->pluck('slot_id')->toArray()
            ]);

            return [
                'success' => true,
                'conflicts' => [],
                'verified_slots' => $inscriptions->pluck('slot_id')->toArray()
            ];
        } finally {
            // SIEMPRE liberar el lock
            $lock->release();
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════════
     * MÉTODO PRINCIPAL 3: releasePaymentLocks
     * ════════════════════════════════════════════════════════════════════════
     * 
     * CUÁNDO SE LLAMA:
     * ----------------
     * - Cuando el usuario cancela el pago
     * - Cuando Redsys notifica un pago fallido
     * - Cuando el carrito se elimina/expira
     * 
     * QUÉ HACE:
     * ---------
     * Elimina las keys "in_payment:..." de Redis para los slots del carrito.
     * Esto permite que otro usuario pueda reservar esos slots.
     * 
     * @param Cart $cart El carrito cuyo pago se canceló/falló
     * @return int Número de locks liberados
     */
    public function releasePaymentLocks(Cart $cart): int
    {
        $inscriptions = $cart->inscriptions()
            ->whereNotNull('slot_id')
            ->get();

        $released = 0;

        foreach ($inscriptions as $inscription) {
            $key = $this->getPaymentLockKey($inscription->session_id, $inscription->slot_id);

            // Solo eliminar si el lock es de ESTE carrito
            $lockData = $this->getPaymentLockData($key);

            if ($lockData && ($lockData['cart_id'] ?? null) == $cart->id) {
                Redis::del($key);
                $released++;
            }
        }

        if ($released > 0) {
            Log::info('PaymentSlotLock: Released payment locks', [
                'cart_id' => $cart->id,
                'released_count' => $released
            ]);
        }

        return $released;
    }

    // ════════════════════════════════════════════════════════════════════════
    // MÉTODOS PRIVADOS DE AYUDA
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Marcar un slot como "en proceso de pago" en Redis
     * 
     * @param int $sessionId
     * @param int $slotId
     * @param int $cartId
     */
    private function markSlotInPayment(int $sessionId, int $slotId, int $cartId): void
    {
        $key = $this->getPaymentLockKey($sessionId, $slotId);

        $data = json_encode([
            'cart_id' => $cartId,
            'started_at' => now()->toIso8601String()
        ]);

        Redis::setex($key, self::PAYMENT_LOCK_TTL, $data);
    }

    /**
     * Verificar si un slot está siendo pagado por OTRO carrito
     * 
     * @param int $sessionId
     * @param int $slotId
     * @param int $currentCartId El carrito actual (para excluirlo)
     * @return array|null Datos del lock si existe y es de otro carrito
     */
    private function checkIfSlotInPayment(int $sessionId, int $slotId, int $currentCartId): ?array
    {
        $key = $this->getPaymentLockKey($sessionId, $slotId);
        $lockData = $this->getPaymentLockData($key);

        if (!$lockData) {
            return null;
        }

        // Si es del mismo carrito, no es conflicto
        if (($lockData['cart_id'] ?? null) == $currentCartId) {
            return null;
        }

        return $lockData;
    }

    /**
     * Verificar si un slot ya fue vendido a otro carrito (confirmado)
     * 
     * @param int $slotId
     * @param int $sessionId
     * @param int $currentCartId
     * @return Inscription|null La inscripción existente si hay conflicto
     */
    private function checkIfSlotSoldToAnother(int $slotId, int $sessionId, int $currentCartId): ?Inscription
    {
        return Inscription::where('slot_id', $slotId)
            ->where('session_id', $sessionId)
            ->where('cart_id', '!=', $currentCartId)
            ->whereHas('cart', function ($query) {
                $query->whereNotNull('confirmation_code');
            })
            ->with('cart')
            ->first();
    }

    /**
     * Verificar si un slot está bloqueado temporalmente por otro carrito
     * (carrito no expirado, no confirmado)
     * 
     * @param int $slotId
     * @param int $sessionId
     * @param int $currentCartId
     * @return SessionTempSlot|null El bloqueo temporal si existe
     */
    private function checkIfSlotBlockedByAnother(int $slotId, int $sessionId, int $currentCartId): ?SessionTempSlot
    {
        return SessionTempSlot::where('slot_id', $slotId)
            ->where('session_id', $sessionId)
            ->where('cart_id', '!=', $currentCartId)
            ->where('expires_on', '>', now())
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Generar la key de Redis para el lock de pago de un slot
     * 
     * @param int $sessionId
     * @param int $slotId
     * @return string
     */
    private function getPaymentLockKey(int $sessionId, int $slotId): string
    {
        return self::PAYMENT_LOCK_PREFIX . "s{$sessionId}:slot{$slotId}";
    }

    /**
     * Obtener los datos de un lock de pago desde Redis
     * 
     * @param string $key
     * @return array|null
     */
    private function getPaymentLockData(string $key): ?array
    {
        $data = Redis::get($key);

        if (!$data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Verificar si un slot específico está en estado "en pago"
     * (Método público para uso en RedisSlotsService si se necesita)
     * 
     * @param int $sessionId
     * @param int $slotId
     * @return bool
     */
    public function isSlotInPayment(int $sessionId, int $slotId): bool
    {
        $key = $this->getPaymentLockKey($sessionId, $slotId);
        return Redis::exists($key) == 1;
    }

    /**
     * Obtener información del lock de pago de un slot
     * (Método público para debugging/admin)
     * 
     * @param int $sessionId
     * @param int $slotId
     * @return array|null
     */
    public function getSlotPaymentInfo(int $sessionId, int $slotId): ?array
    {
        $key = $this->getPaymentLockKey($sessionId, $slotId);
        $data = $this->getPaymentLockData($key);

        if (!$data) {
            return null;
        }

        $ttl = Redis::ttl($key);

        return array_merge($data, [
            'ttl_remaining' => $ttl,
            'expires_in_seconds' => $ttl,
            'key' => $key
        ]);
    }
}
