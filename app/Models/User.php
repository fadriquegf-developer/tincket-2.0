<?php

namespace App\Models;

use App\Scopes\BrandScope;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\AllowUsersTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Backpack\CRUD\app\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasRoles;
    use CrudTrait;
    use HasFactory;
    use SoftDeletes;
    use SetsBrandOnCreate;
    use LogsActivity;
    use AllowUsersTrait;
    use HasApiTokens;

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'allowed_ips'
    ];

    /**
     * Los atributos que deben estar ocultos para serialización.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser casteados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'allowed_ips' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Solo aplicar el BrandScope si NO es engine
        if (get_brand_capability() !== 'engine') {
            static::addGlobalScope(new BrandScope());
        }

        // Evitar eliminar el usuario superadmin
        static::deleting(function ($user) {
            if ($user->id == 1) {
                return false;
            }
        });
    }

    /**
     * Obtiene la lista de marcas del usuario (solo nombres)
     */
    public function getBrandsList(): string
    {
        return $this->brands->pluck('name')->join(', ');
    }

    /**
     * Verifica si el usuario tiene acceso desde la IP actual
     * Soporta rangos CIDR y validación más robusta
     */
    public function canAccessFromIp(string $ip): bool
    {
        // Si no hay IPs configuradas, permitir acceso
        if (empty($this->allowed_ips)) {
            return true;
        }

        // Validar que la IP sea válida
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Log::warning("Invalid IP attempted access: {$ip} for user {$this->id}");
            return false;
        }

        // Verificar cada IP o rango permitido
        foreach ($this->allowed_ips as $allowedIp) {
            // Soporte para IP exacta
            if ($ip === $allowedIp) {
                return true;
            }

            // Soporte para rangos CIDR (ej: 192.168.1.0/24)
            if (strpos($allowedIp, '/') !== false) {
                list($range, $netmask) = explode('/', $allowedIp, 2);
                if ($this->ipInCIDRRange($ip, $range, $netmask)) {
                    return true;
                }
            }

            // Soporte para wildcards (ej: 192.168.1.*)
            if (strpos($allowedIp, '*') !== false) {
                $pattern = str_replace('*', '.*', $allowedIp);
                if (preg_match('/^' . $pattern . '$/', $ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica si una IP está dentro de un rango CIDR
     */
    private function ipInCIDRRange(string $ip, string $range, int $netmask): bool
    {
        $ip_binary = sprintf("%032b", ip2long($ip));
        $range_binary = sprintf("%032b", ip2long($range));
        return substr($ip_binary, 0, $netmask) === substr($range_binary, 0, $netmask);
    }

    /**
     * Hash la contraseña antes de guardarla
     * IMPORTANTE: Este método NO debe aceptar contraseñas ya hasheadas
     * para evitar bypass de validaciones
     */
    public function setPasswordAttribute($value): void
    {
        // Solo procesar si hay valor
        if (!empty($value)) {
            // Si ya es un hash bcrypt válido, no lo vuelvas a hashear
            if (strlen($value) === 60 && str_starts_with($value, '$2y$')) {
                $this->attributes['password'] = $value;
            } else {
                // Si es texto plano, hashearlo
                $this->attributes['password'] = Hash::make($value);
            }
        }
        // Si el valor está vacío, no hacer nada (mantener la contraseña actual)
    }

    /**
     * Envía notificación de reset de contraseña
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_user')->withTimestamps();
    }

    public function updateNotifications()
    {
        return $this->belongsToMany(UpdateNotification::class)->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeSuperAdmins($query)
    {
        $superuserIds = config('superusers.ids', [1]);
        return $query->whereIn('id', $superuserIds);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getIsSuperAdminAttribute(): bool
    {
        return in_array($this->id, config('superusers.ids', [1]));
    }

    public function getAvatarAttribute(): string
    {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
