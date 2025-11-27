<?php

namespace App\Models;

use App\Observers\TpvObserver;
use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Tpv extends BaseModel
{
    use CrudTrait;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use OwnedModelTrait;
    use SetsBrandOnCreate;
    use LogsActivity;

    /** List of all accepted TPV. This must match with 
     * omnipay library name */
    const TPV_TYPES = [
        'Sermepa' => 'Sermepa',
        'Redsys' => 'Redsys',
    ];

    public $fillable = [
        'name',
        'config',
        'omnipay_type',
        'brand_id',
        'is_active',
        'is_test_mode',
        'is_default',
        'priority'
    ];
    protected $casts = [
        'config' => 'array',
    ];

    protected static function booted()
    {
        parent::boot();
        static::observe(TpvObserver::class);
        static::addGlobalScope(new BrandScope());
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function getConfigAttribute($value)
    {
        $config = json_decode($value, true);

        if (!is_array($config)) {
            return [];
        }

        foreach ($config as &$item) {
            // Solo desencriptar si está marcado como encriptado
            if (isset($item['encrypted']) && $item['encrypted']) {
                try {
                    $item['value'] = decrypt($item['value']);
                } catch (\Exception $e) {
                    \Log::error('Error desencriptando TPV config', [
                        'tpv_id' => $this->id,
                        'key' => $item['key']
                    ]);
                    $item['value'] = '***ERROR_DECRYPTING***';
                }
            }
        }

        return $config;
    }

    public function setConfigAttribute($value)
    {
        $sensitiveKeys = [
            'sermepaMerchantKey',
            'apiKey',
            'secret',
            'password',
            'privateKey',
            'token',
            'webhookSecret'
        ];

        if (is_array($value)) {
            foreach ($value as &$item) {
                // Si ya está marcado como encriptado, no re-encriptar
                if (isset($item['encrypted']) && $item['encrypted']) {
                    continue;
                }

                if (in_array($item['key'], $sensitiveKeys)) {
                    $item['value'] = encrypt($item['value']);
                    $item['encrypted'] = true;
                }
            }
        }

        $this->attributes['config'] = json_encode($value);
    }

    // Añadir estos métodos útiles
    public static function getDefaultForCurrentBrand()
    {
        $brandId = get_current_brand_id();

        $tpv = self::where('brand_id', $brandId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$tpv) {
            $tpv = self::where('brand_id', $brandId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->first();
        }

        return $tpv;
    }

    public function scopeAvailableForSelection($query)
    {
        return $query->where('brand_id', get_current_brand_id())
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name');
    }
}
