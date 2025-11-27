<?php

namespace App\Models;

use App\Services\RedisSlotsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Slot extends BaseModel
{
    protected $table = 'slots';
    public $timestamps = false;

    protected $hidden = ['rates_info'];
    protected $casts = [
        'is_locked' => 'boolean',
        'x' => 'integer',
        'y' => 'integer',
        'zone_id' => 'integer',
        'space_id' => 'integer',
        'status_id' => 'integer'
    ];

    protected $fillable = [
        'name',
        'x',
        'y',
        'zone_id',
        'space_id',
        'status_id',
        'comment'
    ];

    /**
     * Relaciones básicas
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Verificar disponibilidad usando Redis
     */
    public function isAvailableForSession(int $sessionId, bool $isTicketOffice = false, bool $isForPack = false): bool
    {
        $session = Session::find($sessionId);
        if (!$session) {
            return false;
        }

        $service = new RedisSlotsService($session);
        return $service->isSlotAvailable($this->id, $isTicketOffice, $isForPack);
    }

    /**
     * Obtener inscripción para una sesión
     */
    public function getInscriptionForSession(int $sessionId)
    {
        $cacheKey = "inscription:{$sessionId}:{$this->id}";

        return Cache::tags(["session:{$sessionId}", "slot:{$this->id}"])
            ->remember($cacheKey, 60, function () use ($sessionId) {
                return Inscription::where('slot_id', $this->id)
                    ->where('session_id', $sessionId)
                    ->whereHas('cart', function ($q) {
                        $q->where(function ($query) {
                            $query->whereNotNull('confirmation_code')
                                ->orWhere('expires_on', '>', now());
                        });
                    })
                    ->first();
            });
    }

    /**
     * Obtener tarifas para una sesión
     */
    public function getRatesForSession(int $sessionId, bool $publicOnly = true)
    {
        $cacheKey = "rates:{$sessionId}:slot:{$this->id}:" . ($publicOnly ? 'public' : 'all');

        return Cache::tags(["session:{$sessionId}", "slot:{$this->id}"])
            ->remember($cacheKey, 300, function () use ($sessionId, $publicOnly) {
                // Obtener tarifas desde AssignatedRate
                return AssignatedRate::where('session_id', $sessionId)
                    ->where('assignated_rate_type', Zone::class)
                    ->where('assignated_rate_id', $this->zone_id)
                    ->when($publicOnly, fn($q) => $q->where('is_public', true))
                    ->with('rate:id,name')
                    ->get()
                    ->map(function ($ar) {
                        return [
                            'id' => $ar->rate->id,
                            'name' => $ar->rate->name,
                            'price' => $ar->price,
                            'max_on_sale' => $ar->max_on_sale,
                            'max_per_order' => $ar->max_per_order,
                            'is_public' => $ar->is_public
                        ];
                    });
            });
    }

    /**
     * Obtener estado completo del slot para una sesión
     */
    public function getStateForSession(int $sessionId)
    {
        $session = Session::find($sessionId);
        if (!$session) {
            return null;
        }

        $service = new RedisSlotsService($session);
        $config = $service->getConfiguration();

        // Buscar el slot en la configuración
        foreach ($config['zones'] ?? [] as $zone) {
            foreach ($zone['slots'] ?? [] as $slot) {
                if ($slot['id'] == $this->id) {
                    return (object) $slot;
                }
            }
        }

        return null;
    }

    /**
     * Limpiar cache del slot
     */
    public function clearCache(int $sessionId): void
    {
        Cache::tags(["session:{$sessionId}", "slot:{$this->id}"])->flush();

        // Invalidar configuración completa
        $session = Session::find($sessionId);
        if ($session) {
            $service = new RedisSlotsService($session);
            $service->invalidateSlot($this->id);
        }
    }

    /**
     * Precargar disponibilidad masiva
     */
    public static function preloadAvailability(array $slotIds, int $sessionId, bool $isTicketOffice = false, bool $isForPack = false): array
    {
        if (empty($slotIds)) {
            return [];
        }

        $session = Session::find($sessionId);
        if (!$session) {
            return [];
        }

        $service = new RedisSlotsService($session);
        return $service->checkBulkAvailability($slotIds, $isTicketOffice, $isForPack);
    }

    /**
     * Métodos de compatibilidad con código legacy
     */
    public function isAvailableFor($session_id, $isticketOffice = false, $isForAPack = false)
    {
        return $this->isAvailableForSession($session_id, $isticketOffice, $isForAPack);
    }

    public function getInscription($sessionId = null)
    {
        if (!$sessionId && isset($this->pivot_session_id)) {
            $sessionId = $this->pivot_session_id;
        }

        if (!$sessionId) {
            return null;
        }

        return $this->getInscriptionForSession($sessionId);
    }

    /**
     * Este método ya no es necesario con Redis
     * Lo mantenemos para compatibilidad pero retorna un array vacío
     */
    public function arrayCacheSlot()
    {
        if (!isset($this->pivot_session_id)) {
            throw new \Exception('arrayCacheSlot requires pivot_session_id to be set');
        }

        $state = $this->getStateForSession($this->pivot_session_id);

        return [
            "session_id" => $this->pivot_session_id,
            "slot_id" => $this->id,
            "zone_id" => $this->pivot_zone_id ?? $this->zone_id,
            "cart_id" => $state->cart_id ?? null,
            "is_locked" => (bool)($state->is_locked ?? false),
            "comment" => $state->comment ?? $this->comment,
            "rates_info" => $state->rates ?? []
        ];
    }
}
