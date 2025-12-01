<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * MODELO: PartialRefund
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Representa una devolución parcial de un carrito.
 * 
 * Cuando un cliente compra varias entradas y quiere devolver solo algunas,
 * se crea un registro de PartialRefund con los items (inscripciones) devueltos.
 * 
 * ESTADOS:
 * - pending: Solicitud creada, pendiente de procesar
 * - processing: En proceso de devolución con Redsys
 * - completed: Devolución completada exitosamente
 * - failed: Devolución fallida
 * 
 * @property int $id
 * @property int $cart_id
 * @property int|null $payment_id
 * @property int|null $brand_id
 * @property float $amount
 * @property string $reason
 * @property string|null $notes
 * @property string $status
 * @property string|null $refund_reference
 * @property \Carbon\Carbon|null $refunded_at
 * @property int|null $processed_by
 * @property array|null $details
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read Cart $cart
 * @property-read Payment|null $payment
 * @property-read Brand|null $brand
 * @property-read User|null $processedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection|PartialRefundItem[] $items
 */
class PartialRefund extends Model
{
    use SoftDeletes;
    use SetsBrandOnCreate;

    protected $table = 'partial_refunds';

    protected $fillable = [
        'cart_id',
        'payment_id',
        'brand_id',
        'amount',
        'reason',
        'notes',
        'status',
        'refund_reference',
        'refunded_at',
        'processed_by',
        'details',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'details' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const REASON_CUSTOMER_REQUEST = 'customer_request';
    public const REASON_EVENT_CANCELLED = 'event_cancelled';
    public const REASON_DUPLICATE_PAYMENT = 'duplicate_payment';
    public const REASON_ADMIN_MANUAL = 'admin_manual';
    public const REASON_OTHER = 'other';

    protected static function booted()
    {
        // Aplicar BrandScope si no es engine
        if (function_exists('get_brand_capability') && get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════════════════════════

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class)->withTrashed();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartialRefundItem::class);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════════════════════════

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS DE ACCIÓN
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Marcar como en proceso
     */
    public function markAsProcessing(): self
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();

        return $this;
    }

    /**
     * Marcar como completado
     * 
     * @param string|null $reference Referencia del reembolso (de Redsys o manual)
     * @param array $additionalDetails Detalles adicionales para guardar
     */
    public function markAsCompleted(?string $reference = null, array $additionalDetails = []): self
    {
        $this->status = self::STATUS_COMPLETED;
        $this->refund_reference = $reference;
        $this->refunded_at = now();
        $this->processed_by = auth()->id();

        // Merge detalles existentes con nuevos
        $details = $this->details ?? [];
        $details = array_merge($details, $additionalDetails, [
            'completed_at' => now()->toIso8601String(),
            'completed_by' => auth()->user()?->email,
        ]);
        $this->details = $details;

        $this->save();

        return $this;
    }

    /**
     * Marcar como fallido
     * 
     * @param string $errorMessage Mensaje de error
     * @param array $additionalDetails Detalles adicionales
     */
    public function markAsFailed(string $errorMessage, array $additionalDetails = []): self
    {
        $this->status = self::STATUS_FAILED;

        $details = $this->details ?? [];
        $details = array_merge($details, $additionalDetails, [
            'failed_at' => now()->toIso8601String(),
            'error_message' => $errorMessage,
        ]);
        $this->details = $details;

        $this->save();

        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════════

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForCart($query, int $cartId)
    {
        return $query->where('cart_id', $cartId);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Obtener el texto del motivo traducido
     */
    public function getReasonTextAttribute(): string
    {
        return __('refund.reasons.' . $this->reason) !== 'refund.reasons.' . $this->reason
            ? __('refund.reasons.' . $this->reason)
            : $this->reason;
    }

    /**
     * Obtener el badge de estado para mostrar en la UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '<span class="badge bg-warning">Pendiente</span>',
            self::STATUS_PROCESSING => '<span class="badge bg-info">Procesando</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success">Completado</span>',
            self::STATUS_FAILED => '<span class="badge bg-danger">Fallido</span>',
            default => '<span class="badge bg-secondary">Desconocido</span>',
        };
    }

    /**
     * Obtener el número de inscripciones devueltas
     */
    public function getInscriptionCountAttribute(): int
    {
        return $this->items()->count();
    }
}