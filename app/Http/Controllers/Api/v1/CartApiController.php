<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Cart;
use App\Models\GroupPack;
use App\Models\Session;
use App\Models\Inscription;
use App\Services\Api\CartService;
use App\Services\InscriptionService;
use App\Services\Payment\PaymentServiceFactory;
use App\Exceptions\SlotNotAvailableException;
use App\Exceptions\SessionFullException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\PaymentSlotLockService;

class CartApiController extends \App\Http\Controllers\Api\ApiController
{
    private CartService $service;
    private PaymentServiceFactory $paymentServiceFactory;
    private InscriptionService $inscriptionService;
    private PaymentSlotLockService $paymentSlotLockService;

    public function __construct()
    {
        $this->service = new CartService();
        $this->paymentServiceFactory = new PaymentServiceFactory();
        $this->inscriptionService = new InscriptionService();
        $this->paymentSlotLockService = new PaymentSlotLockService();

        // Middleware para verificar expiración y confirmación
        $this->middleware(\App\Http\Middleware\Api\v1\CartExpiration::class)
            ->only(['update', 'destroy', 'extendTime']);
        $this->middleware(\App\Http\Middleware\Api\v1\CartConfirmation::class)
            ->only(['update', 'destroy', 'extendTime']);
    }

    /**
     * Mostrar carrito con todas sus relaciones
     * ✅ DESHABILITAR BrandScope en relaciones de brands hijos
     */
    public function show($id)
    {
        $cart = $this->getCartBuilder($id)
            ->with([
                'inscriptions.slot',
                // ✅ DESHABILITAR BrandScope en session
                'inscriptions.session' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                // ✅ DESHABILITAR BrandScope en event
                'inscriptions.session.event' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                'inscriptions.session.space.location',
                // ✅ DESHABILITAR BrandScope en rate
                'inscriptions.rate' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                // ✅ Para packs también
                'groupPacks.pack' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                'groupPacks.inscriptions.session' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                'groupPacks.inscriptions.session.event' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                'groupPacks.inscriptions.session.space.location',
                'groupPacks.inscriptions.slot',
                'groupPacks.inscriptions.rate' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                },
                // ✅ Gift cards
                'gift_cards.event' => function ($query) {
                    $query->withoutGlobalScope(\App\Scopes\BrandScope::class);
                }
            ])
            ->firstOrFail();

        // Reorganizar datos para mejor legibilidad
        $cart->setAttribute('packs', $cart->groupPacks->map(function ($gp) {
            $pack = clone $gp->pack;
            $pack->setAttribute('cart_pack_id', $gp->id);
            $pack->setAttribute('inscriptions', $gp->inscriptions);
            return $pack;
        }));

        unset($cart->groupPacks);

        return $this->json($cart);
    }

    /**
     * Crear nuevo carrito
     */
    public function store(Request $request)
    {
        try {
            $cart = $this->service->createCart($request);
            return $this->show($cart->id);
        } catch (\Exception $e) {
            Log::error('Error creando carrito', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => 'No se pudo crear el carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar sección del carrito
     */
    public function update(Cart $cart, $section, Request $request)
    {
        try {
            $methodName = 'set' . Str::studly($section);

            if (!method_exists($this->service, $methodName)) {
                return $this->json([
                    'error' => "Sección '$section' no válida"
                ], 404);
            }

            DB::beginTransaction();

            $data = $this->service->{$methodName}($cart, $request);

            DB::commit();

            return $this->json($data, $data ? 200 : 204);
        } catch (SlotNotAvailableException $e) {
            DB::rollBack();
            return $this->json([
                'error' => 'Slot no disponible',
                'message' => $e->getMessage()
            ], 409); // Conflict

        } catch (SessionFullException $e) {
            DB::rollBack();
            return $this->json([
                'error' => 'Sesión completa',
                'message' => $e->getMessage()
            ], 409);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error actualizando carrito sección: $section", [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => 'Error actualizando carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar elementos del carrito
     * ✅ DESHABILITAR BrandScope al buscar elementos
     */
    public function destroy(Cart $cart, $type, $id)
    {
        try {
            DB::beginTransaction();

            switch ($type) {
                case 'pack':
                    // ✅ DESHABILITAR BrandScope al buscar GroupPack
                    $result = $this->destroyPack(
                        $cart,
                        GroupPack::withoutGlobalScope(\App\Scopes\BrandScope::class)->find($id)
                    );
                    break;

                case 'session':
                    // ✅ DESHABILITAR BrandScope al buscar Session
                    $result = $this->destroySession(
                        $cart,
                        Session::withoutGlobalScope(\App\Scopes\BrandScope::class)->find($id)
                    );
                    break;

                case 'inscription':
                    // ✅ DESHABILITAR BrandScope al buscar Inscription
                    $result = $this->destroyInscription(
                        $cart,
                        Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)->find($id)
                    );
                    break;

                case 'gift-card':
                    $result = $this->destroyGiftCard($cart, $id);
                    break;

                default:
                    DB::rollBack();
                    return $this->json(['error' => "Tipo '$type' no válido"], 400);
            }

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error eliminando del carrito", [
                'cart_id' => $cart->id,
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Error eliminando del carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar pack del carrito
     * ✅ DESHABILITAR BrandScope al cargar inscripciones
     */
    private function destroyPack(Cart $cart, ?GroupPack $pack)
    {
        if (!$pack) {
            return $this->json(['error' => 'Pack no encontrado'], 404);
        }

        // ✅ Cargar inscripciones con BrandScope deshabilitado
        $pack->load(['inscriptions' => function ($q) {
            $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->with(['session' => function ($sq) {
                    $sq->withoutGlobalScope(\App\Scopes\BrandScope::class);
                }]);
        }]);

        // Liberar slots de inscripciones numeradas
        foreach ($pack->inscriptions as $inscription) {
            if ($inscription->slot_id) {
                try {
                    $this->inscriptionService->releaseSlot($inscription);
                } catch (\Exception $e) {
                    Log::warning('Error liberando slot de pack', [
                        'inscription_id' => $inscription->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $inscription->delete();
            }
        }

        $pack->delete();

        return $this->json(null, 204);
    }

    /**
     * Eliminar inscripción individual
     * ✅ Ya recibe la inscripción con BrandScope deshabilitado desde destroy()
     */
    private function destroyInscription(Cart $cart, ?Inscription $inscription)
    {
        if (!$inscription) {
            return $this->json(['error' => 'Inscripción no encontrada'], 404);
        }

        // Si tiene slot, usar el servicio para liberarlo correctamente
        if ($inscription->slot_id) {
            // ✅ Cargar session si no está cargada
            if (!$inscription->relationLoaded('session')) {
                $inscription->load(['session' => function ($q) {
                    $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->withTrashed();
                }]);
            }

            if ($inscription->session) {
                $this->inscriptionService->releaseSlot($inscription);
            } else {
                Log::warning('Inscripción sin session válida al eliminar', [
                    'inscription_id' => $inscription->id,
                    'slot_id' => $inscription->slot_id
                ]);
                $inscription->delete();
            }
        } else {
            // Si es una tarifa con reglas especiales
            if ($inscription->rate && $inscription->rate->has_rule) {
                $cart->inscriptions()
                    ->withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->where('rate_id', $inscription->rate_id)
                    ->where('session_id', $inscription->session_id)
                    ->delete();
            } else {
                $inscription->delete();
            }
        }

        return $this->json(null, 204);
    }

    /**
     * Eliminar todas las inscripciones de una sesión
     * ✅ DESHABILITAR BrandScope al cargar inscripciones
     */
    private function destroySession(Cart $cart, ?Session $session)
    {
        if (!$session) {
            return $this->json(['error' => 'Sesión no encontrada'], 404);
        }

        // ✅ Obtener inscripciones con BrandScope deshabilitado
        $inscriptions = $cart->inscriptions()
            ->withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->where('session_id', $session->id)
            ->with(['session' => function ($q) {
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class);
            }])
            ->get();

        // Liberar slots numerados
        foreach ($inscriptions as $inscription) {
            if ($inscription->slot_id) {
                try {
                    $this->inscriptionService->releaseSlot($inscription);
                } catch (\Exception $e) {
                    Log::warning('Error liberando slot', [
                        'inscription_id' => $inscription->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $inscription->delete();
            }
        }

        return $this->json(null, 204);
    }

    /**
     * Eliminar gift card
     * ✅ La relación gift_cards() ya tiene withoutGlobalScope en el modelo Cart
     */
    private function destroyGiftCard(Cart $cart, $id)
    {
        $cart->gift_cards()->where('id', $id)->delete();
        return $this->json(null, 204);
    }

    /**
     * Verificar duplicados
     * ✅ DESHABILITAR BrandScope para buscar en TODOS los carritos del sistema
     */
    public function checkDuplicated($id)
    {
        $cart = $this->getCartBuilder($id)
            ->notExpired()
            ->whereNull('confirmation_code')
            ->withInscriptions()
            ->orHas('gift_cards')
            ->firstOrFail();

        $slots = $cart->allInscriptions->pluck('slot_id')->filter();
        $sessionId = $cart->allInscriptions->first()->session_id ?? null;

        if (!$sessionId || $slots->isEmpty()) {
            return $this->json(false, 200);
        }

        // ✅ DESHABILITAR BrandScope para buscar duplicados en TODOS los brands
        // Esto es importante porque queremos evitar duplicados en TODO el sistema,
        // no solo en el brand actual
        $duplicatedCart = Cart::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->notExpired()
            ->where('id', '!=', $cart->id)
            ->whereHas('allInscriptions', function ($q) use ($sessionId, $slots) {
                // ✅ También deshabilitar aquí
                $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                    ->where('session_id', $sessionId)
                    ->whereIn('slot_id', $slots);
            })
            ->exists();

        return $this->json($duplicatedCart, 200);
    }

    /**
     * Obtener datos de pago
     */
    public function getPayment($id)
    {
        // ─────────────────────────────────────────────────────────────────────
        // PASO 1: Buscar el carrito (sin cambios)
        // ─────────────────────────────────────────────────────────────────────
        $cart = $this->getCartBuilder($id)
            ->notExpired()
            ->whereNull('confirmation_code')
            ->where(function ($q) {
                $q->withInscriptions()
                    ->orWhereHas('gift_cards');
            })
            ->first();

        if (!$cart) {
            return $this->json(['error' => 'Carrito no válido'], 404);
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 2: ✅ NUEVO - Verificar y bloquear slots antes de pagar
        // ─────────────────────────────────────────────────────────────────────
        // 
        // ¿Qué hace lockSlotsForPayment()?
        //   1. Verifica que cada slot no haya sido vendido a otro cliente
        //   2. Verifica que cada slot no esté siendo pagado por otro carrito
        //   3. Marca cada slot como "in_payment" en Redis (TTL 10 min)
        //   4. Extiende la fecha de expiración del carrito
        // 
        // Si algún slot no está disponible, lanza SlotNotAvailableException
        // con los detalles de qué slots tienen problemas.

        try {
            $lockResult = $this->paymentSlotLockService->lockSlotsForPayment($cart);

            // Log para debugging (opcional, puedes quitarlo en producción)
            Log::info('Payment slots locked successfully', [
                'cart_id' => $cart->id,
                'locked_slots' => count($lockResult['locked_slots'] ?? []),
                'cart_expires_on' => $lockResult['cart_expires_on'] ?? null
            ]);
        } catch (SlotNotAvailableException $e) {
            // ─────────────────────────────────────────────────────────────────
            // Algún slot no está disponible - devolver error 409
            // ─────────────────────────────────────────────────────────────────
            // 
            // El frontend debe manejar este error mostrando un mensaje como:
            // "Algunos asientos ya no están disponibles. Por favor, revisa tu carrito."
            // 
            // La respuesta incluye detalles de qué slots tienen problemas:
            // {
            //     "error": "slot_not_available",
            //     "message": "Algunos asientos ya no están disponibles",
            //     "conflicts": [
            //         {
            //             "slot_id": 123,
            //             "slot_name": "Fila A, Butaca 5",
            //             "reason": "already_sold"
            //         }
            //     ]
            // }

            Log::warning('Payment blocked - slots not available', [
                'cart_id' => $cart->id,
                'conflicts' => $e->getConflicts()
            ]);

            return $this->json([
                'error' => 'slot_not_available',
                'message' => $e->getMessage(),
                'conflicts' => $e->getConflicts(),
                'conflict_slot_ids' => $e->getConflictSlotIds()
            ], 409);
        }

        // ─────────────────────────────────────────────────────────────────────
        // PASO 3: Crear payment y devolver datos (sin cambios)
        // ─────────────────────────────────────────────────────────────────────
        try {
            $platform = $cart->price_sold == 0 ? 'Free' : 'Redsys_Redirect';
            $service = $this->paymentServiceFactory->create($platform);
            $service->purchase($cart);

            return $this->json($service->getData());
        } catch (\Exception $e) {
            // ─────────────────────────────────────────────────────────────────
            // Si falla la creación del payment, liberar los locks
            // ─────────────────────────────────────────────────────────────────
            // Esto es importante para no dejar slots bloqueados si algo falla
            // después de haberlos bloqueado.

            try {
                $this->paymentSlotLockService->releasePaymentLocks($cart);
            } catch (\Exception $releaseError) {
                Log::error('Failed to release payment locks after error', [
                    'cart_id' => $cart->id,
                    'original_error' => $e->getMessage(),
                    'release_error' => $releaseError->getMessage()
                ]);
            }

            Log::error('Error procesando pago', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'error' => 'No se pudo procesar el pago'
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de slots antes de pagar
     * 
     * Este endpoint es OPCIONAL pero mejora la experiencia de usuario.
     * El frontend puede llamarlo antes de mostrar el formulario de pago
     * para verificar que los slots siguen disponibles.
     * 
     * Si los slots están disponibles, devuelve 200 OK.
     * Si hay conflictos, devuelve 409 Conflict con detalles.
     * 
     * @param int $id ID del carrito
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSlotsAvailability($id)
    {
        $cart = $this->getCartBuilder($id)
            ->notExpired()
            ->whereNull('confirmation_code')
            ->first();

        if (!$cart) {
            return $this->json(['error' => 'Carrito no válido'], 404);
        }

        try {
            // Usamos el mismo método que getPayment() pero sin bloquear
            // Solo verificamos disponibilidad
            $result = $this->paymentSlotLockService->lockSlotsForPayment($cart);

            // Si llegamos aquí, los slots fueron bloqueados exitosamente
            // Los mantenemos bloqueados porque el usuario va a pagar

            return $this->json([
                'available' => true,
                'slots_count' => count($result['locked_slots'] ?? []),
                'cart_expires_on' => $result['cart_expires_on'] ?? null
            ]);
        } catch (SlotNotAvailableException $e) {
            return $this->json([
                'available' => false,
                'message' => $e->getMessage(),
                'conflicts' => $e->getConflicts(),
                'conflict_slot_ids' => $e->getConflictSlotIds()
            ], 409);
        }
    }

    /**
     * Obtener pago desde email
     * 
     * Permite carritos con:
     * - confirmation_code NULL (nunca pagados)
     * - confirmation_code que empieza con 'XXXXXXXXX' (pendientes de repago)
     */
    public function getPaymentForEmail($token)
    {
        $cart = $this->getCartBuilder($token)
            ->notExpired()
            ->where(function ($q) {
                // Sin confirmation_code O con código pendiente de pago
                $q->whereNull('confirmation_code')
                    ->orWhere('confirmation_code', 'like', 'XXXXXXXXX%');
            })
            ->where(function ($q) {
                $q->withInscriptions()
                    ->orWhereHas('gift_cards');
            })
            ->first();

        if (!$cart) {
            return $this->json(['error' => 'Carrito no válido'], 404);
        }

        try {
            $platform = $cart->price_sold == 0 ? 'Free' : 'Redsys_Redirect';
            $service = $this->paymentServiceFactory->create($platform);
            $service->purchase($cart);

            return $this->json($service->getData());
        } catch (\Exception $e) {
            Log::error('Error procesando pago desde email', [
                'cart_token' => $token,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'No se pudo procesar el pago'
            ], 500);
        }
    }

    /**
     * Verificar si el pago está confirmado
     * 
     * Un carrito se considera pagado si:
     * 1. Tiene confirmation_code no null
     * 2. El confirmation_code NO empieza con 'XXXXXXXXX' (pendientes de pago)
     */
    public function checkPaymentPaid($token)
    {
        $cart = $this->getCartBuilder($token)
            ->whereNotNull('confirmation_code')
            ->where('confirmation_code', 'not like', 'XXXXXXXXX%')  // Excluir pendientes de pago
            ->first();

        return $this->json((bool) $cart, 200);
    }

    /**
     * Extender tiempo del carrito
     */
    public function extendTime(Cart $cart)
    {
        // El middleware ya maneja la extensión
        return $this->json([
            'expires_on' => $cart->expires_on->toIso8601String(),
            'minutes_remaining' => $cart->expires_on->diffInMinutes(now())
        ], 200);
    }

    /**
     * Marcar carrito como expirado
     */
    public function expiredTime(Cart $cart)
    {
        $cart->expiredTime();

        return $this->json([
            'message' => 'Carrito marcado como expirado',
            'expired' => true
        ], 200);
    }

    /**
     * Constructor de query para cart
     * ✅ Este método SÍ debe mantener ownedByBrand() porque solo queremos
     * carritos del brand actual (no de otros brands)
     */
    private function getCartBuilder($id)
    {
        $query = Cart::ownedByBrand()->with('brand');

        if (is_numeric($id)) {
            return $query->where('id', $id);
        }

        return $query->where('token', $id);
    }
}
