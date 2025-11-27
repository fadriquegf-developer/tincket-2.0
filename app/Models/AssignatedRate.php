<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignatedRate extends BaseModel
{
    protected $table = 'assignated_rates';

    protected $fillable = [
        'session_id',
        'rate_id',
        'price',
        'max_on_sale',
        'max_per_order',
        'available_since',
        'available_until',
        'is_public',
        'is_private',
        'max_per_code',
        'validator_class',
        'assignated_rate_type',
        'assignated_rate_id',
    ];

    protected $casts = [
        'validator_class' => 'array',
        'is_public' => 'boolean',
        'is_private' => 'boolean',
    ];

    public $timestamps = false;

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }

    protected $appends = ['form_id'];

    public function getFormIDAttribute()
    {
        return $this->rate->form_id ?? null;
    }

    /**
     * Calcula las posiciones libres para esta tarifa asignada
     */
    public function getCountFreePositionsAttribute()
    {
        if (!$this->session_id || !$this->max_on_sale) {
            return 0;
        }

        $session = Session::find($this->session_id);
        if (!$session) {
            return 0;
        }

        $car_ttl = $session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

        // Inscripciones confirmadas
        $confirmed = Inscription::where('session_id', $this->session_id)
            ->where('rate_id', $this->rate_id)
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->whereNotNull('carts.confirmation_code')
            ->count();

        // Carritos no expirados
        $notExpired = Inscription::where('session_id', $this->session_id)
            ->where('rate_id', $this->rate_id)
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->where('carts.expires_on', '>', \Carbon\Carbon::now()->subMinutes($car_ttl))
            ->whereNull('carts.confirmation_code')
            ->count();

        $blocked = $confirmed + $notExpired;
        $available = $this->max_on_sale - $blocked;

        return min([$session->getFreePositions(), max(0, $available)]);
    }
}
