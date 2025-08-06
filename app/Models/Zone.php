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

    public function rates()
    {
        if (!isset($this->pivot_session_id)) {
            // Si prefieres no lanzar excepción:
            return $this->morphToMany(Rate::class, 'assignated_rate', 'assignated_rates', 'assignated_rate_id', 'rate_id')
                ->whereRaw('1=0');
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
                'validator_class',
            ]);
    }
}
