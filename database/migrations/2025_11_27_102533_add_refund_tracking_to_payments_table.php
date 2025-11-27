<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // ═══════════════════════════════════════════════════════════════
            // CAMPO: requires_refund
            // ───────────────────────────────────────────────────────────────
            // Tipo: Boolean (TINYINT en MySQL)
            // Default: false
            // 
            // Propósito: Marcar pagos que se cobraron pero no se pueden cumplir
            // porque los slots ya fueron vendidos a otro cliente.
            // 
            // Uso:
            //   $payment->requires_refund = true;
            //   
            // Query para encontrar pagos pendientes de reembolso:
            //   Payment::where('requires_refund', true)
            //          ->whereNull('refunded_at')
            //          ->get();
            // ═══════════════════════════════════════════════════════════════
            $table->boolean('requires_refund')
                ->default(false)
                ->after('gateway_response')
                ->comment('Indica si este pago necesita ser reembolsado');

            // ═══════════════════════════════════════════════════════════════
            // CAMPO: refund_reason
            // ───────────────────────────────────────────────────────────────
            // Tipo: VARCHAR(50), nullable
            // 
            // Propósito: Categorizar el motivo del reembolso para estadísticas
            // 
            // Valores posibles:
            //   - 'duplicate_slots': El slot ya fue vendido a otro (race condition)
            //   - 'expired_session': La sesión expiró antes de confirmar
            //   - 'cancelled_event': El evento fue cancelado
            //   - 'customer_request': El cliente solicitó cancelación
            //   - 'admin_manual': Reembolso manual por admin
            // ═══════════════════════════════════════════════════════════════
            $table->string('refund_reason', 50)
                ->nullable()
                ->after('requires_refund')
                ->comment('Categoría del motivo de reembolso');

            // ═══════════════════════════════════════════════════════════════
            // CAMPO: refund_details
            // ───────────────────────────────────────────────────────────────
            // Tipo: JSON, nullable
            // 
            // Propósito: Guardar toda la información del conflicto para:
            //   - Debugging
            //   - Soporte al cliente
            //   - Auditoría
            // 
            // Estructura ejemplo:
            // {
            //   "conflicts": [
            //     {
            //       "slot_id": 123,
            //       "slot_name": "Fila A, Butaca 5",
            //       "session_id": 456,
            //       "reason": "already_sold",
            //       "existing_cart_id": 789,
            //       "existing_confirmation_code": "0000123-TK45"
            //     }
            //   ],
            //   "detected_at": "2025-11-27T12:30:00+00:00",
            //   "cart_expires_on": "2025-11-27T12:15:00+00:00",
            //   "payment_completed_at": "2025-11-27T12:30:00+00:00"
            // }
            // ═══════════════════════════════════════════════════════════════
            $table->json('refund_details')
                ->nullable()
                ->after('refund_reason')
                ->comment('Detalles JSON del conflicto (slots, carritos, etc.)');

            // ═══════════════════════════════════════════════════════════════
            // CAMPO: refunded_at
            // ───────────────────────────────────────────────────────────────
            // Tipo: TIMESTAMP, nullable
            // 
            // Propósito: Registrar cuándo se procesó el reembolso
            // 
            // NULL = Pendiente de reembolso
            // Fecha = Ya reembolsado
            // 
            // Esto permite distinguir entre:
            //   - Pagos que necesitan reembolso pero aún no se ha hecho
            //   - Pagos que ya fueron reembolsados
            // ═══════════════════════════════════════════════════════════════
            $table->timestamp('refunded_at')
                ->nullable()
                ->after('refund_details')
                ->comment('Fecha/hora en que se procesó el reembolso');

            // ═══════════════════════════════════════════════════════════════
            // CAMPO: refund_reference
            // ───────────────────────────────────────────────────────────────
            // Tipo: VARCHAR(100), nullable
            // 
            // Propósito: Guardar el código de referencia del reembolso
            // 
            // Ejemplos:
            //   - Redsys: Número de operación de devolución
            //   - Manual: "MANUAL-2025-001"
            //   - PayPal: Transaction ID del refund
            // ═══════════════════════════════════════════════════════════════
            $table->string('refund_reference', 100)
                ->nullable()
                ->after('refunded_at')
                ->comment('Código de referencia del reembolso (Redsys, manual, etc.)');

            // ═══════════════════════════════════════════════════════════════
            // ÍNDICE: payments_pending_refunds_index
            // ───────────────────────────────────────────────────────────────
            // Tipo: Índice compuesto (requires_refund, refunded_at)
            // 
            // Propósito: Optimizar la query más común:
            //   "Dame todos los pagos que necesitan reembolso y aún no se han procesado"
            // 
            //   SELECT * FROM payments 
            //   WHERE requires_refund = 1 AND refunded_at IS NULL;
            // 
            // Sin índice: Full table scan (lento con millones de pagos)
            // Con índice: Index scan (rápido)
            // ═══════════════════════════════════════════════════════════════
            $table->index(
                ['requires_refund', 'refunded_at'],
                'payments_pending_refunds_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Eliminar el índice primero (MySQL requiere esto)
            $table->dropIndex('payments_pending_refunds_index');

            // Eliminar las columnas
            $table->dropColumn([
                'requires_refund',
                'refund_reason',
                'refund_details',
                'refunded_at',
                'refund_reference'
            ]);
        });
    }
};
