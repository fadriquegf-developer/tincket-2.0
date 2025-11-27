<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

/**
 * A zone is an area of Slots inside an Space
 */
class Zone extends BaseModel
{
    use CrudTrait;
    use LogsActivity;

    protected $fillable = ['name', 'space_id', 'color'];
    public $timestamps = false;

    public $pivot_session_id;

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    /** Slots relacionados vía la tabla pivot space_configuration_details */
    public function slots()
    {
        return $this->hasMany(Slot::class, 'zone_id');
    }

    /**
     * Obtener tarifas de la zona para una sesión específica
     * 
     * @param int|null $sessionId
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function rates($sessionId = null)
    {
        // Si no se proporciona sessionId, intentar obtenerlo del pivot
        if (!$sessionId && isset($this->pivot_session_id)) {
            $sessionId = $this->pivot_session_id;
        }

        // Si aún no tenemos sessionId, log y retornar relación vacía
        if (!$sessionId) {

            // Retornar una relación vacía
            return $this->morphToMany(
                Rate::class,
                'assignated_rate',
                'assignated_rates',
                'assignated_rate_id',
                'rate_id'
            )->whereRaw('1=0');
        }

        return $this->morphToMany(
            Rate::class,
            'assignated_rate',
            'assignated_rates',
            'assignated_rate_id',
            'rate_id'
        )
            ->wherePivot('assignated_rate_type', self::class)
            ->wherePivot('session_id', $sessionId)
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
                'validator_class',
            ]);
    }

    /**
     * Método helper para obtener tarifas con session_id explícito
     * Preferir este método sobre rates() cuando sea posible
     * 
     * @param int $sessionId
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function getRatesForSession(int $sessionId)
    {
        return $this->rates($sessionId);
    }

    /**
     * Verificar si la zona tiene tarifas para una sesión
     * 
     * @param int $sessionId
     * @return bool
     */
    public function hasRatesForSession(int $sessionId): bool
    {
        return $this->rates($sessionId)->exists();
    }
}
