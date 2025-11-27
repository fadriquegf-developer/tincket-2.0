<?php

namespace App\Models;

use App\Scopes\BrandScope;
use App\Traits\LogsActivity;
use App\Traits\OwnedModelTrait;
use App\Traits\SetsBrandOnCreate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Client extends BaseModel
{
    use CrudTrait;
    use LogsActivity;
    use SetsBrandOnCreate;
    use SoftDeletes;
    use OwnedModelTrait;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'mobile_phone',
        'locale',
        'date_birth',
        'dni',
        'province',
        'city',
        'address',
        'postal_code',
        'newsletter',
        'brand_id',
        // password NO debe estar en fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at'
    ];

    protected $casts = [
        'newsletter' => 'boolean',
        'date_birth' => 'date:Y-m-d',
        'email_verified_at' => 'datetime'
    ];

    protected $appends = ['full_name'];

    protected static function booted()
    {
        static::addGlobalScope(new BrandScope());

        // Validación básica antes de guardar
        static::saving(function ($client) {
            // Si es nuevo cliente, verificar que tiene password
            if (!$client->exists && empty($client->attributes['password'])) {
                // Generar un password temporal seguro
                $tempPassword = 'Temp' . rand(1000, 9999) . '!';
                $client->attributes['password'] = Hash::make($tempPassword);

                \Log::warning('Cliente creado con password temporal', [
                    'client_id' => $client->email,
                    'brand_id' => $client->brand_id
                ]);
            }

            // Normalizar email
            if ($client->email) {
                $client->email = strtolower(trim($client->email));
            }
        });
    }

    /**
     * Mutador mejorado para password
     * Detecta mejor si el valor ya es un hash
     */
    public function setPasswordAttribute($value)
    {
        // Si el campo está vacío, no hacer nada
        if (empty($value)) {
            return;
        }

        // Detectar si ya es un hash (60 caracteres, empieza con $2y$)
        if (strlen($value) === 60 && str_starts_with($value, '$2y$')) {
            \Log::warning('Password ya está hasheado, se omite', [
                'client_id' => $this->id ?? 'nuevo',
                'email' => $this->email ?? 'sin email'
            ]);
            return;
        }

        // Validaciones básicas
        if (strlen($value) < 6) {
            throw new \InvalidArgumentException('El password debe tener al menos 6 caracteres');
        }

        if (strlen($value) > 100) {
            throw new \InvalidArgumentException('El password es demasiado largo');
        }

        // Hashear el password UNA sola vez
        $this->attributes['password'] = Hash::make($value);

    }

    /**
     * Verificar password
     */
    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Actualizar password con validación del actual
     */
    public function updatePassword(string $newPassword, string $currentPassword = null): bool
    {
        // Si se proporciona password actual, verificarlo
        if ($currentPassword !== null && !$this->checkPassword($currentPassword)) {
            throw new \InvalidArgumentException('El password actual es incorrecto');
        }

        $this->password = $newPassword;
        return $this->save();
    }

    // ========== RELACIONES ==========

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function inscriptions()
    {
        return $this->hasManyThrough(
            Inscription::class,
            Cart::class,
            'client_id',
            'cart_id',
            'id',
            'id'
        );
    }

    public function answers()
    {
        return $this->hasMany(FormFieldAnswer::class);
    }

    // ========== ACCESSORS ==========

    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->surname);
    }

    public function getFullNameEmailAttribute(): string
    {
        // Ofuscar email para privacidad
        $emailParts = explode('@', $this->email);
        $emailName = substr($emailParts[0], 0, 2) . str_repeat('*', max(0, strlen($emailParts[0]) - 2));
        $obfuscatedEmail = $emailName . '@' . ($emailParts[1] ?? '');

        return "{$this->surname}, {$this->name} ({$obfuscatedEmail})";
    }

    /**
     * Obtener número de sesiones (con cache)
     */
    public function getNumSessionsAttribute(): int
    {
        return Cache::remember(
            "client_{$this->id}_num_sessions",
            300, // 5 minutos
            function () {
                return $this->getNumSessions();
            }
        );
    }

    /**
     * Calcular número de sesiones a las que ha asistido
     * Método simplificado usando queries directas
     */
    public function getNumSessions(): int
    {
        return Session::query()
            ->join('inscriptions', 'sessions.id', '=', 'inscriptions.session_id')
            ->join('carts', 'inscriptions.cart_id', '=', 'carts.id')
            ->where('carts.client_id', $this->id)
            ->whereNotNull('carts.confirmation_code')
            ->whereNull('carts.deleted_at')
            ->distinct('sessions.id')
            ->count('sessions.id');
    }

    // ========== SCOPES ==========

    public function scopeNewsletter($query)
    {
        return $query->where('newsletter', true);
    }

    public function scopeWithEmail($query)
    {
        return $query->whereNotNull('email')
            ->where('email', '!=', '');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== MÉTODOS DE CAMPOS PERSONALIZADOS ==========

    /**
     * Obtener respuesta de un campo específico
     */
    public function getFieldAnswer($fieldId)
    {
        return $this->answers()
            ->where('field_id', $fieldId)
            ->first();
    }

    /**
     * Obtener valor de un campo específico
     */
    public function getFieldValue($fieldId, $default = null)
    {
        $answer = $this->getFieldAnswer($fieldId);
        return $answer ? $answer->answer : $default;
    }

    /**
     * Guardar respuesta de campo personalizado
     */
    public function saveFieldAnswer($fieldId, $value)
    {
        return $this->answers()->updateOrCreate(
            ['field_id' => $fieldId],
            ['answer' => $value]
        );
    }

    /**
     * Verificar si el cliente tiene todos los campos requeridos
     */
    public function hasAllRequiredFields(): bool
    {
        $requiredFields = FormField::where('brand_id', $this->brand_id)
            ->whereNull('deleted_at')
            ->where('config->required', true)
            ->pluck('id');

        if ($requiredFields->isEmpty()) {
            return true;
        }

        $answeredFields = $this->answers()
            ->whereIn('field_id', $requiredFields)
            ->whereNotNull('answer')
            ->where('answer', '!=', '')
            ->pluck('field_id');

        return $requiredFields->diff($answeredFields)->isEmpty();
    }

    // ========== MÉTODOS DE UTILIDAD ==========

    /**
     * Array para exportación (sin datos sensibles)
     */
    public function toExportArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile_phone' => $this->mobile_phone,
            'locale' => $this->locale,
            'province' => $this->province,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'newsletter' => $this->newsletter ? 'Si' : 'No',
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'num_sessions' => $this->getNumSessions()
        ];
    }

    /**
     * Serialización segura
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Eliminar campos sensibles
        unset($array['password']);
        unset($array['remember_token']);

        // Ofuscar DNI si existe
        if (isset($array['dni']) && $array['dni']) {
            $array['dni'] = substr($array['dni'], 0, 2) .
                str_repeat('*', max(0, strlen($array['dni']) - 4)) .
                substr($array['dni'], -2);
        }

        return $array;
    }
}
