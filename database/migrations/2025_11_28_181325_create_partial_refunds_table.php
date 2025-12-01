<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * MIGRACIÓN: Tabla partial_refunds
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Esta tabla registra el historial de devoluciones parciales de un carrito.
 * Cuando un cliente compra 6 entradas y quiere devolver solo 2, se crea un
 * registro aquí con el detalle de qué inscripciones se devolvieron.
 * 
 * El carrito permanece activo, pero las inscripciones devueltas se eliminan
 * (soft delete) para liberar las butacas.
 * 
 * RELACIONES:
 * - partial_refunds -> cart (many-to-one)
 * - partial_refunds -> payment (many-to-one, opcional)
 * - partial_refund_items -> partial_refunds (many-to-one)
 * - partial_refund_items -> inscription (many-to-one, guarda snapshot)
 * 
 * @author YesWeTicket
 * @since 2025-11-28
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════════
        // TABLA PRINCIPAL: partial_refunds
        // ═══════════════════════════════════════════════════════════════════
        Schema::create('partial_refunds', function (Blueprint $table) {
            $table->id();

            // ───────────────────────────────────────────────────────────────
            // Relaciones
            // ───────────────────────────────────────────────────────────────
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('payment_id')->nullable();
            $table->unsignedInteger('brand_id')->nullable();

            // ───────────────────────────────────────────────────────────────
            // Datos del reembolso
            // ───────────────────────────────────────────────────────────────

            // Importe total devuelto (suma de price_sold de inscripciones)
            $table->decimal('amount', 10, 2);

            // Motivo de la devolución
            $table->string('reason', 50)->default('customer_request');

            // Notas adicionales
            $table->text('notes')->nullable();

            // ───────────────────────────────────────────────────────────────
            // Estado del reembolso
            // ───────────────────────────────────────────────────────────────

            // pending = solicitado, processing = en proceso con Redsys, 
            // completed = completado, failed = fallido
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');

            // Referencia del reembolso (de Redsys o manual)
            $table->string('refund_reference', 100)->nullable();

            // Fecha en que se procesó el reembolso
            $table->timestamp('refunded_at')->nullable();

            // ───────────────────────────────────────────────────────────────
            // Auditoría
            // ───────────────────────────────────────────────────────────────

            // Usuario que procesó la devolución
            // Nota: usar unsignedInteger si la tabla users usa increments()
            // o unsignedBigInteger si usa bigIncrements()
            $table->unsignedInteger('processed_by')->nullable();

            // Detalles adicionales en JSON (respuesta de Redsys, etc.)
            $table->json('details')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ───────────────────────────────────────────────────────────────
            // Foreign keys
            // ───────────────────────────────────────────────────────────────
            $table->foreign('cart_id')
                ->references('id')
                ->on('carts')
                ->onDelete('cascade');

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('set null');

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->onDelete('cascade');

            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // ───────────────────────────────────────────────────────────────
            // Índices
            // ───────────────────────────────────────────────────────────────
            $table->index('cart_id');
            $table->index('status');
            $table->index('created_at');
        });

        // ═══════════════════════════════════════════════════════════════════
        // TABLA DE ITEMS: partial_refund_items
        // ═══════════════════════════════════════════════════════════════════
        // 
        // Guarda el detalle de cada inscripción devuelta.
        // Almacenamos un snapshot de los datos porque la inscripción
        // se va a eliminar (soft delete).
        // 
        Schema::create('partial_refund_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partial_refund_id')->constrained('partial_refunds')->onDelete('cascade');

            // ID de la inscripción original (para referencia)
            $table->unsignedInteger('inscription_id');

            // ───────────────────────────────────────────────────────────────
            // Snapshot de la inscripción (para mantener el historial)
            // ───────────────────────────────────────────────────────────────
            $table->unsignedInteger('session_id')->nullable();
            $table->unsignedInteger('slot_id')->nullable();
            $table->unsignedInteger('rate_id')->nullable();

            // Datos de la inscripción en el momento de la devolución
            $table->string('session_name', 255)->nullable();
            $table->string('event_name', 255)->nullable();
            $table->string('slot_name', 255)->nullable();
            $table->string('rate_name', 255)->nullable();
            $table->string('barcode', 100)->nullable();

            // Precio devuelto
            $table->decimal('price', 10, 2);
            $table->decimal('price_sold', 10, 2);

            $table->timestamps();

            // ───────────────────────────────────────────────────────────────
            // Foreign keys
            // ───────────────────────────────────────────────────────────────
            // partial_refund_id ya tiene FK definida arriba con foreignId()

            // No ponemos FK a inscriptions porque se va a soft-delete
            // y queremos mantener la referencia

            // ───────────────────────────────────────────────────────────────
            // Índices
            // ───────────────────────────────────────────────────────────────
            // partial_refund_id ya tiene índice por la FK
            $table->index('inscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partial_refund_items');
        Schema::dropIfExists('partial_refunds');
    }
};
