<?php

namespace App\Services\Payment;

use App\Models\Cart;
use App\Models\Inscription;
use App\Models\PartialRefund;
use App\Models\PartialRefundItem;
use App\Scopes\BrandScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * SERVICIO: PartialRefundService
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Maneja toda la lógica de negocio para devoluciones parciales de carritos.
 * 
 * Funcionalidades principales:
 * - Crear solicitud de devolución parcial
 * - Procesar devolución con Redsys
 * - Marcar devolución como completada manualmente
 * - Eliminar inscripciones y liberar butacas
 * 
 * @author YesWeTicket
 * @since 2025-11-28
 */
class PartialRefundService
{
    protected ?RedsysRefundService $redsysService = null;

    public function __construct(?RedsysRefundService $redsysService = null)
    {
        $this->redsysService = $redsysService;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS PRINCIPALES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crear una solicitud de devolución parcial
     * 
     * Este método:
     * 1. Valida que las inscripciones pertenecen al carrito
     * 2. Crea el registro de PartialRefund
     * 3. Crea los items con snapshot de cada inscripción
     * 4. Elimina las inscripciones (soft delete) para liberar butacas
     * 5. Añade comentario al carrito
     * 
     * @param Cart $cart El carrito
     * @param array $inscriptionIds IDs de las inscripciones a devolver
     * @param string $reason Motivo de la devolución
     * @param string|null $notes Notas adicionales
     * @return array ['success' => bool, 'partial_refund' => PartialRefund|null, 'message' => string]
     */
    public function createPartialRefund(
        Cart $cart,
        array $inscriptionIds,
        string $reason = 'customer_request',
        ?string $notes = null
    ): array {
        // ───────────────────────────────────────────────────────────────────
        // Validaciones
        // ───────────────────────────────────────────────────────────────────

        if (empty($inscriptionIds)) {
            return $this->errorResponse('Debe seleccionar al menos una inscripción para devolver.');
        }

        // Verificar que el carrito tiene pago
        if (!$cart->payment || !$cart->payment->paid_at) {
            return $this->errorResponse('Este carrito no tiene un pago confirmado.');
        }

        // Obtener inscripciones válidas (que pertenecen al carrito y no están eliminadas)
        $inscriptions = Inscription::withoutGlobalScope(BrandScope::class)
            ->where('cart_id', $cart->id)
            ->whereIn('id', $inscriptionIds)
            ->whereNull('deleted_at')
            ->get();

        if ($inscriptions->isEmpty()) {
            return $this->errorResponse('Las inscripciones seleccionadas no son válidas o ya fueron devueltas.');
        }

        // Verificar que no se están devolviendo TODAS las inscripciones
        // (en ese caso debería usarse la devolución completa)
        $totalInscriptions = Inscription::withoutGlobalScope(BrandScope::class)
            ->where('cart_id', $cart->id)
            ->whereNull('deleted_at')
            ->count();

        if ($inscriptions->count() >= $totalInscriptions) {
            return $this->errorResponse(
                'No puede devolver todas las inscripciones con devolución parcial. ' .
                    'Use la opción de devolución completa.'
            );
        }

        // ───────────────────────────────────────────────────────────────────
        // Crear la devolución parcial en una transacción
        // ───────────────────────────────────────────────────────────────────

        try {
            $partialRefund = DB::transaction(function () use ($cart, $inscriptions, $reason, $notes) {
                // Calcular importe total a devolver
                $amount = $inscriptions->sum('price_sold');

                // Crear registro principal
                $partialRefund = PartialRefund::create([
                    'cart_id' => $cart->id,
                    'payment_id' => $cart->payment?->id,
                    'brand_id' => $cart->brand_id,
                    'amount' => $amount,
                    'reason' => $reason,
                    'notes' => $notes,
                    'status' => PartialRefund::STATUS_PENDING,
                    'details' => [
                        'requested_by' => auth()->user()?->email,
                        'requested_at' => now()->toIso8601String(),
                        'inscription_count' => $inscriptions->count(),
                        'total_cart_inscriptions' => $cart->inscriptions()->count(),
                    ],
                ]);

                // Crear items (snapshot de cada inscripción)
                foreach ($inscriptions as $inscription) {
                    PartialRefundItem::createFromInscription($partialRefund, $inscription);
                }

                // Eliminar inscripciones (soft delete)
                // El InscriptionObserver se encargará de liberar los slots en Redis
                foreach ($inscriptions as $inscription) {
                    $inscription->deleted_user_id = auth()->id();
                    $inscription->save();
                    $inscription->delete();
                }

                // Añadir comentario al carrito
                $inscriptionDetails = $inscriptions->map(function ($i) {
                    $slot = $i->slot?->name ?? 'Sin butaca';
                    $rate = $i->rate?->name ?? 'Sin tarifa';
                    return "- {$slot} ({$rate}): " . number_format($i->price_sold, 2) . " €";
                })->join("\n");

                $comment = "\n\n[DEVOLUCIÓN PARCIAL SOLICITADA " . now()->format('d/m/Y H:i') . "]\n";
                $comment .= "ID: #{$partialRefund->id}\n";
                $comment .= "Importe: " . number_format($amount, 2) . " €\n";
                $comment .= "Inscripciones devueltas ({$inscriptions->count()}):\n{$inscriptionDetails}\n";
                $comment .= "Motivo: " . __('refund.reasons.' . $reason) . "\n";
                if ($notes) {
                    $comment .= "Notas: {$notes}\n";
                }
                $comment .= "Solicitado por: " . auth()->user()?->email;

                $cart->comment = trim($cart->comment . $comment);
                $cart->save();

                return $partialRefund;
            });

            // Log
            Log::info('Partial refund created', [
                'partial_refund_id' => $partialRefund->id,
                'cart_id' => $cart->id,
                'amount' => $partialRefund->amount,
                'inscription_count' => $inscriptions->count(),
                'user_id' => auth()->id(),
            ]);

            return [
                'success' => true,
                'partial_refund' => $partialRefund->fresh(['items']),
                'message' => "Devolución parcial creada correctamente. " .
                    "Se han devuelto {$inscriptions->count()} inscripciones " .
                    "por un total de " . number_format($partialRefund->amount, 2) . " €.",
            ];
        } catch (\Exception $e) {
            Log::error('Error creating partial refund', [
                'cart_id' => $cart->id,
                'inscription_ids' => $inscriptionIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Error al crear la devolución parcial: ' . $e->getMessage());
        }
    }

    /**
     * Procesar devolución parcial con Redsys
     * 
     * @param PartialRefund $partialRefund
     * @return array ['success' => bool, 'message' => string, ...]
     */
    public function processWithRedsys(PartialRefund $partialRefund): array
    {
        if (!$partialRefund->isPending()) {
            return $this->errorResponse('Esta devolución ya fue procesada o está en proceso.');
        }

        if (!$partialRefund->payment) {
            return $this->errorResponse('No se encontró el pago asociado a esta devolución.');
        }

        // Verificar que tenemos el servicio de Redsys
        if (!$this->redsysService) {
            $this->redsysService = new RedsysRefundService();
        }

        // Marcar como en proceso
        $partialRefund->markAsProcessing();

        try {
            // Convertir a céntimos
            $amountCents = (int) round($partialRefund->amount * 100);

            // Procesar con Redsys
            $result = $this->redsysService->processRefund(
                $partialRefund->payment,
                $amountCents,
                $partialRefund->reason
            );

            if ($result['success']) {
                // Marcar como completado
                $partialRefund->markAsCompleted($result['refund_reference'], [
                    'redsys_response' => $result,
                    'amount_cents' => $amountCents,
                ]);

                // Actualizar comentario del carrito
                $cart = $partialRefund->cart;
                $comment = "\n\n[DEVOLUCIÓN PARCIAL PROCESADA " . now()->format('d/m/Y H:i') . "]\n";
                $comment .= "ID: #{$partialRefund->id}\n";
                $comment .= "Ref: {$result['refund_reference']}\n";
                $comment .= "Importe: " . number_format($partialRefund->amount, 2) . " €\n";
                $comment .= "Procesado por: " . auth()->user()?->email;

                $cart->comment = trim($cart->comment . $comment);
                $cart->save();

                Log::info('Partial refund processed with Redsys', [
                    'partial_refund_id' => $partialRefund->id,
                    'refund_reference' => $result['refund_reference'],
                    'amount' => $partialRefund->amount,
                ]);

                return [
                    'success' => true,
                    'message' => "Devolución procesada correctamente. Referencia: {$result['refund_reference']}",
                    'refund_reference' => $result['refund_reference'],
                ];
            } else {
                // Marcar como fallido
                $partialRefund->markAsFailed($result['message'], [
                    'redsys_response' => $result,
                ]);

                return $this->errorResponse($result['message']);
            }
        } catch (\Exception $e) {
            $partialRefund->markAsFailed($e->getMessage());

            Log::error('Error processing partial refund with Redsys', [
                'partial_refund_id' => $partialRefund->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error al procesar con Redsys: ' . $e->getMessage());
        }
    }

    /**
     * Marcar devolución parcial como completada manualmente
     * 
     * @param PartialRefund $partialRefund
     * @param string $reference Referencia del reembolso manual
     * @param string|null $notes Notas adicionales
     * @return array
     */
    public function markAsCompletedManually(
        PartialRefund $partialRefund,
        string $reference,
        ?string $notes = null
    ): array {
        if (!$partialRefund->isPending() && !$partialRefund->isFailed()) {
            return $this->errorResponse('Esta devolución ya fue procesada.');
        }

        try {
            $partialRefund->markAsCompleted($reference, [
                'manual' => true,
                'manual_notes' => $notes,
            ]);

            // Actualizar comentario del carrito
            $cart = $partialRefund->cart;
            $comment = "\n\n[DEVOLUCIÓN PARCIAL MANUAL " . now()->format('d/m/Y H:i') . "]\n";
            $comment .= "ID: #{$partialRefund->id}\n";
            $comment .= "Ref: {$reference}\n";
            $comment .= "Importe: " . number_format($partialRefund->amount, 2) . " €\n";
            if ($notes) {
                $comment .= "Notas: {$notes}\n";
            }
            $comment .= "Procesado por: " . auth()->user()?->email;

            $cart->comment = trim($cart->comment . $comment);
            $cart->save();

            Log::info('Partial refund marked as completed manually', [
                'partial_refund_id' => $partialRefund->id,
                'reference' => $reference,
            ]);

            return [
                'success' => true,
                'message' => 'Devolución marcada como completada correctamente.',
            ];
        } catch (\Exception $e) {
            Log::error('Error marking partial refund as completed', [
                'partial_refund_id' => $partialRefund->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error: ' . $e->getMessage());
        }
    }

    /**
     * Obtener el historial de devoluciones parciales de un carrito
     * 
     * @param Cart $cart
     * @return Collection
     */
    public function getRefundHistory(Cart $cart): Collection
    {
        return PartialRefund::withoutGlobalScope(BrandScope::class)
            ->where('cart_id', $cart->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener las inscripciones disponibles para devolver de un carrito
     * (las que no han sido devueltas aún)
     * 
     * @param Cart $cart
     * @return Collection
     */
    public function getRefundableInscriptions(Cart $cart): Collection
    {
        return Inscription::withoutGlobalScope(BrandScope::class)
            ->where('cart_id', $cart->id)
            ->whereNull('deleted_at')
            ->with(['session.event', 'slot', 'rate'])
            ->get();
    }

    /**
     * Calcular el importe total devuelto de un carrito
     * 
     * @param Cart $cart
     * @return float
     */
    public function getTotalRefunded(Cart $cart): float
    {
        return PartialRefund::withoutGlobalScope(BrandScope::class)
            ->where('cart_id', $cart->id)
            ->where('status', PartialRefund::STATUS_COMPLETED)
            ->sum('amount');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS AUXILIARES
    // ═══════════════════════════════════════════════════════════════════════════

    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'partial_refund' => null,
            'message' => $message,
        ];
    }
}
