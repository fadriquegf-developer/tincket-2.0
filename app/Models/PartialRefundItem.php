<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * MODELO: PartialRefundItem
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * Representa una inscripción individual que fue devuelta como parte de
 * una devolución parcial.
 * 
 * Almacena un snapshot de los datos de la inscripción en el momento de la
 * devolución, ya que la inscripción original será soft-deleted.
 * 
 * @property int $id
 * @property int $partial_refund_id
 * @property int $inscription_id
 * @property int|null $session_id
 * @property int|null $slot_id
 * @property int|null $rate_id
 * @property string|null $session_name
 * @property string|null $event_name
 * @property string|null $slot_name
 * @property string|null $rate_name
 * @property string|null $barcode
 * @property float $price
 * @property float $price_sold
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read PartialRefund $partialRefund
 * @property-read Inscription|null $inscription
 */
class PartialRefundItem extends Model
{
    protected $table = 'partial_refund_items';

    protected $fillable = [
        'partial_refund_id',
        'inscription_id',
        'session_id',
        'slot_id',
        'rate_id',
        'session_name',
        'event_name',
        'slot_name',
        'rate_name',
        'barcode',
        'price',
        'price_sold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_sold' => 'decimal:2',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════════════════════════

    public function partialRefund(): BelongsTo
    {
        return $this->belongsTo(PartialRefund::class);
    }

    /**
     * Relación con la inscripción original (puede estar soft-deleted)
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class)->withTrashed();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // MÉTODOS ESTÁTICOS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crear un item a partir de una inscripción
     * Guarda un snapshot de todos los datos relevantes
     * 
     * @param PartialRefund $partialRefund
     * @param Inscription $inscription
     * @return self
     */
    public static function createFromInscription(PartialRefund $partialRefund, Inscription $inscription): self
    {
        return self::create([
            'partial_refund_id' => $partialRefund->id,
            'inscription_id' => $inscription->id,
            'session_id' => $inscription->session_id,
            'slot_id' => $inscription->slot_id,
            'rate_id' => $inscription->rate_id,
            'session_name' => $inscription->session?->name,
            'event_name' => $inscription->session?->event?->name,
            'slot_name' => $inscription->slot?->name,
            'rate_name' => $inscription->rate?->name,
            'barcode' => $inscription->barcode,
            'price' => $inscription->price,
            'price_sold' => $inscription->price_sold,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Obtener descripción completa del item para mostrar en UI
     */
    public function getFullDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->event_name) {
            $parts[] = $this->event_name;
        }

        if ($this->session_name) {
            $parts[] = $this->session_name;
        }

        if ($this->slot_name) {
            $parts[] = $this->slot_name;
        }

        if ($this->rate_name) {
            $parts[] = "({$this->rate_name})";
        }

        return implode(' - ', $parts) ?: 'Sin descripción';
    }
}
