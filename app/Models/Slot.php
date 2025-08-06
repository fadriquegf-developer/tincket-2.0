<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An slot is the area/place where spectator is located during a session
 */
class Slot extends BaseModel
{

    // this is a JSON stored in cache that we don't want it to JSON output.
    // See App\Services\Api\SlotCacheService.    
    protected $hidden = ['rates_info'];
    protected $casts = [
        'is_locked' => 'boolean'
    ];
    public $timestamps = false;


    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }


    public function rates()
    {
        if (!isset($this->pivot_session_id)) {
            throw new \Exception("Rates of Slot for which Session?");
        }

        return $this->morphToMany(
            Rate::class,
            'assignated_rate',
            'assignated_rates',
            'assignated_rate_id',
            'rate_id'
        )
            ->wherePivot('assignated_rate_type', self::class)
            ->wherePivot('session_id', $this->pivot_session_id)
            ->withPivot([
                'id',
                'price',
                'session_id',
                'max_on_sale',
                'max_per_order',
                'assignated_rate_type',
                'available_since',
                'available_until',
                'is_public',
                'is_private',
                'max_per_code',
                'validator_class'
            ]);
    }


    /**
     * Because an Slot can belong to different Inscription (but only one confirmed),
     * it will be only possible to access to Inscription from Slot if we have arrived
     * here using the inscription->slot relation
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @throws \Exception
     */
    public function getInscriptionAttribute()
    {
        if ($this->relationLoaded('inscription')) {
            return $this->getRelation('inscription');
        }

        if (!isset($this->pivot_session_id)) {
            throw new \Exception("Slot for which Session? pivot_session_id attribute needs to be set");
        }


        $inscriptions = Inscription::sharedLock()->select(['inscriptions.*'])
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->where(function ($query) {
                // an slot belongs to and inscription if it is: confirmed or still not expired
                return $query
                    ->whereNotNull('carts.confirmation_code') // confirmed
                    ->orWhere('carts.expires_on', '>', Carbon::now()) // or not expired
                ;
            })
            ->where('inscriptions.slot_id', $this->id)
            ->where('inscriptions.session_id', $this->pivot_session_id)
            ->get();

        if ($inscriptions->count() > 1) {
            $headers = 'From: noreply@javajan.com' . "\r\n" .
                'Reply-To: noreply@javajan.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $message = sprintf("More than one Inscription related to this Slot (%s) for Session ID (%s). This should not be possible", $this->id, $this->pivot_session_id);
            $email_sent = mail('fadrique.javajan@gmail.com', 'TINCKET: Conflicte de butaques', $message, $headers);
            logger()->warning(sprintf("%s. Notified by mail: %s", $message, $email_sent));
        }

        $this->setRelation('inscription', $inscriptions->first());

        return $this->getRelation('inscription');
    }

    public function session()
    {
        if (!isset($this->pivot_session_id)) {
            throw new \Exception('$this->pivot_session_id must be set to retrieve session');
        }

        $builder = Session::select(['sessions.*', \DB::raw("$this->pivot_session_id as session_id")]);

        return new BelongsTo(
            $builder,
            $this,
            'pivot_session_id',
            'id',
            'session'
        );
    }

    public function sessionSlot()
    {
        if (!isset($this->pivot_session_id)) {
            throw new \Exception("Slot for which Session? pivot_session_id attribute needs to be set");
        }

        return $this->hasMany(SessionSlot::class)->where('session_id', $this->pivot_session_id)->whereNotNull('status_id');
    }

    public function sessionTempSlot()
    {
        if (!isset($this->pivot_session_id)) {
            throw new \Exception("Slot for which Session? pivot_session_id attribute needs to be set");
        }

        return $this->hasMany(SessionTempSlot::class)->where('session_id', $this->pivot_session_id)->notExpired()->whereNotNull('status_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Unlike cascade_rate which returns only public rates, this methods
     * returns the cascade of all rates: public or not
     */

    public function getAllCascadeRatesAttribute()
    {
        if (!isset($this->pivot_session_id)) {
            return collect();
        }

        // Propaga el session_id a la zona
        if ($this->zone) {
            $this->zone->pivot_session_id = $this->pivot_session_id;
        }

        $sessionRates = $this->session?->rates()->get() ?? collect();
        $zoneRates = $this->zone?->rates()->get() ?? collect();
        $slotRates = $this->rates()->get();

        \Log::debug('Slot all_cascade_rates', [
            'slot_id' => $this->id,
            'session_id' => $this->pivot_session_id,
            'session_rates_count' => $sessionRates->count(),
            'zone_rates_count' => $zoneRates->count(),
            'slot_rates_count' => $slotRates->count(),
        ]);

        return $sessionRates
            ->merge($zoneRates)
            ->merge($slotRates)
            ->unique('id')
            ->sortBy(fn($r) => $r->pivot->id ?? PHP_INT_MAX)
            ->values();
    }

    public function getCascadeRatesAttribute()
    {
        $rates = collect([])->keyBy('id')
            ->merge($this->session->rates()->wherePivot('is_public', true)->getResults())
            ->merge($this->zone->rates()->wherePivot('is_public', true)->getResults())
            ->merge($this->rates()->wherePivot('is_public', true)->getResults());

        return $rates->keyBy('id')->sortBy('pivot.id')->values();
    }

    public function isAvailableFor($session_id, $isticketOffice = false, $isForAPack = false)
    {
        $this->pivot_session_id = $session_id;

        return $this->getIsAvailableAttribute($isticketOffice, $isForAPack);
    }

    public function getIsAvailableAttribute($isTicketOffice = false, $isForAPack = false)
    {
        // Verificar si los slots tienen un status que no permite la disponibilidad
        $auxSessionSlot = $this->sessionSlot()->whereNotNull('status_id')->where('status_id', '!=', 6);
        $auxSessionTempSlot = $this->sessionTempSlot()->whereNotNull('status_id')->where('status_id', '!=', 6);

        // Condiciones especiales para taquilla
        if ($isTicketOffice) {
            $this->applyTicketOfficeConditions($auxSessionSlot, $auxSessionTempSlot);
        }

        // Condiciones especiales para packs
        if ($isForAPack) {
            $this->applyPackConditions($auxSessionSlot, $auxSessionTempSlot);
        }

        // Verificar condiciones de inscripción y carrito
        $isSlotAvailable = $this->checkInscriptionAndCart();


        // Verificar que no haya slots con status conflictivos
        $noConflictingSlots = $auxSessionSlot->count() === 0 && $auxSessionTempSlot->count() === 0;

        // El slot está disponible si se cumplen ambas condiciones
        return $isSlotAvailable && $noConflictingSlots;
    }

    /**
     * Aplica condiciones específicas para taquilla, ignorando ciertos status.
     */
    private function applyTicketOfficeConditions($sessionSlot, $tempSessionSlot)
    {
        // Ignorar slots reservados y de reducción de movilidad en taquilla
        $sessionSlot->whereNotIn('status_id', [3, 7]);
        $tempSessionSlot->whereNotIn('status_id', [3, 7]);
    }

    /**
     * Aplica condiciones específicas para packs, ignorando ciertos status.
     */
    private function applyPackConditions($sessionSlot, $tempSessionSlot)
    {
        // Ignorar slots reservados para packs
        $sessionSlot->where('status_id', '!=', 8);
        $tempSessionSlot->where('status_id', '!=', 8);
    }

    /**
     * Verifica las condiciones de disponibilidad del slot basadas en la inscripción y el carrito.
     */
    private function checkInscriptionAndCart()
    {
        $inscription = $this->inscription;
        $cart = $inscription ? $inscription->cart : null;

        // Slot disponible solo si no tiene inscripción o carrito, o si el carrito ha expirado y no está confirmado
        if (!$inscription || !$cart) {
            return true; // Disponible porque no hay inscripción o carrito
        }

        return $cart->is_expired && !$cart->is_confirmed;
    }


    /**
     * Converts the product to array.
     *
     * @return array
     */
    public function arrayCacheSlot()
    {
        return [
            "session_id" => $this->pivot_session_id,
            "slot_id" => $this->id,
            "zone_id" => $this->pivot_zone_id,
            "cart_id" => !$this->is_available && isset($this->inscription->cart) ? $this->inscription->cart->id : null,
            "is_locked" => !$this->is_available,
            "comment" => $this->comment,
            "rates_info" => $this->all_cascade_rates
        ];
    }
}
