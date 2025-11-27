<?php

namespace App\Observers;

use App\Jobs\UpdateSessionSlotCache;
use App\Models\Session;
use App\Services\RedisSlotsService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SessionObserver
{

    /**
     * Propiedad para almacenar campos originales
     */
    private array $originalFields = [];

    /**
     * Se dispara cuando se crea una nueva sesión
     */
    public function created(Session $session)
    {
        // Heredar estados de butacas del espacio a la sesión
        $this->inheritSlotsFromSpace($session);

        // Lanzamos la creación de cache en Redis
        if ($session->is_numbered) {
            UpdateSessionSlotCache::dispatch($session);
        }
    }

    /**
     * Se dispara después de que un modelo Session se haya guardado (create o update).
     */
    public function saved(Session $session)
    {
        // Procesar imágenes
        $oldImages = $session->getOriginal('images') ?? [];
        $this->processImages($session);
        $newImages = $session->images ?? [];

        // Si cambiaron las imágenes, invalidar cache
        if ($oldImages != $newImages) {
            $this->invalidateSessionCache($session);
        }
    }

    /**
     * Handle the Session "updating" event.
     */
    public function updating(Session $session): void
    {
        // Guardar campos originales para comparación posterior
        $this->originalFields = [
            'max_places' => $session->getOriginal('max_places'),
            'limit_x_100' => $session->getOriginal('limit_x_100'),
            'autolock_type' => $session->getOriginal('autolock_type'),
            'autolock_n' => $session->getOriginal('autolock_n'),
            'space_id' => $session->getOriginal('space_id'),
            'is_numbered' => $session->getOriginal('is_numbered'),
            'inscription_starts_on' => $session->getOriginal('inscription_starts_on'),
            'inscription_ends_on' => $session->getOriginal('inscription_ends_on'),
            'visibility' => $session->getOriginal('visibility'),
            'private' => $session->getOriginal('private'),
        ];
    }


    /**
     * Handle the Session "updated" event.
     */
    public function updated(Session $session): void
    {
        $needsCacheInvalidation = false;
        $needsRedisRegeneration = false;

        // Campos que afectan la disponibilidad
        $availabilityFields = [
            'max_places',
            'limit_x_100',
            'inscription_starts_on',
            'inscription_ends_on',
            'visibility',
            'private'
        ];

        // Campos que afectan la configuración de slots
        $slotConfigFields = [
            'autolock_type',
            'autolock_n',
            'space_id',
            'is_numbered'
        ];

        // Verificar cambios en campos de disponibilidad
        foreach ($availabilityFields as $field) {
            if ($session->isDirty($field)) {
                $needsCacheInvalidation = true;
                break;
            }
        }

        // Verificar cambios en campos de configuración de slots
        foreach ($slotConfigFields as $field) {
            if ($session->isDirty($field)) {
                $needsRedisRegeneration = true;
                break;
            }
        }

        // Invalidar cache si es necesario
        if ($needsCacheInvalidation) {
            $this->invalidateSessionCache($session);
            $this->invalidateEventListCache($session);
        }

        // Regenerar Redis si es necesario y es numerada
        if ($session->is_numbered && ($needsRedisRegeneration || $needsCacheInvalidation)) {
            UpdateSessionSlotCache::dispatch($session);
        }
    }

    /**
     * Handle the Session "deleted" event.
     */
    public function deleted(Session $session): void
    {
        // Limpiar completamente la cache
        $this->clearAllSessionCache($session);
        $this->invalidateEventListCache($session);

        // Limpiar la cache de Redis cuando se elimina una sesión
        if ($session->is_numbered) {
            try {
                $redisService = new RedisSlotsService($session);
                $redisService->clearAllCache();
            } catch (\Exception $e) {
                Log::error("Failed to clear Redis cache for deleted session {$session->id}: " . $e->getMessage());
            }
        }

        // Eliminar archivos de storage
        $this->deleteSessionFiles($session);
    }

    /**
     * Handle the Session "restored" event.
     */
    public function restored(Session $session): void
    {
        // Invalidar cache al restaurar
        $this->invalidateSessionCache($session);

        if ($session->is_numbered) {
            UpdateSessionSlotCache::dispatch($session);
        }
    }

    /**
     * Handle the Session "force deleted" event.
     */
    public function forceDeleted(Session $session): void
    {
        // Limpiar completamente la cache de Redis
        $this->clearAllSessionCache($session);

        if ($session->is_numbered) {
            try {
                $redisService = new RedisSlotsService($session);
                $redisService->clearAllCache();
            } catch (\Exception $e) {
                Log::error("Failed to clear Redis cache for force deleted session {$session->id}: " . $e->getMessage());
            }
        }

        // Eliminar archivos de storage
        $this->deleteSessionFiles($session);
    }



    /**
     * Invalidar toda la cache relacionada con la sesión
     */
    private function invalidateSessionCache(Session $session): void
    {
        try {
            // Cache tags de la sesión
            Cache::tags(["session:{$session->id}"])->flush();

            // Cache específicos
            $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';

            // Limpiar caches de disponibilidad
            Cache::forget("{$brandPrefix}:free:s{$session->id}");
            Cache::forget("{$brandPrefix}:available_web:s{$session->id}");
            Cache::forget("{$brandPrefix}:blocked:s{$session->id}");
            Cache::forget("session_{$session->id}_public_rates_formatted");
            Cache::forget("session_{$session->id}_general_rate");

            // Si es numerada, invalidar Redis también
            if ($session->is_numbered) {
                $redisService = new RedisSlotsService($session);
                $redisService->invalidateAvailabilityCache();
            }
        } catch (\Exception $e) {
            Log::error("SessionObserver: Error invalidating cache", [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Hereda los estados de las butacas y zonas del espacio a la nueva sesión
     */
    private function inheritSlotsFromSpace(Session $session)
    {
        // Solo proceder si la sesión es numerada y tiene un espacio asociado
        if (!$session->is_numbered || !$session->space_id) {
            return;
        }

        $space = $session->space;
        if (!$space) {
            Log::warning("[SessionObserver] No se encontró el espacio {$session->space_id} para la sesión {$session->id}");
            return;
        }


        // Obtener todos los slots del espacio con sus estados y zonas
        $slots = $space->slots()
            ->select('id', 'status_id', 'comment', 'zone_id', 'name', 'x', 'y')
            ->get();

        if ($slots->isEmpty()) {
            return;
        }

        // Preparar los datos para inserción masiva
        $sessionSlots = [];
        $timestamp = now();

        foreach ($slots as $slot) {
            // Solo crear SessionSlot si el slot tiene un estado definido
            if ($slot->status_id !== null) {
                $sessionSlots[] = [
                    'session_id' => $session->id,
                    'slot_id' => $slot->id,
                    'status_id' => $slot->status_id,
                    'comment' => $slot->comment,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        // Inserción masiva para mejor rendimiento
        if (!empty($sessionSlots)) {
            try {
                DB::table('session_slot')->insert($sessionSlots);
            } catch (\Exception $e) {
                Log::error("[SessionObserver] Error al heredar slots: " . $e->getMessage());
            }
        } else {
        }
    }

    public static function processImages(Session $session): void
    {
        $brand = get_current_brand()->code_name;
        $sessionId = $session->id;
        $basePath = "uploads/{$brand}/session/{$sessionId}/";

        // Obtener imágenes nuevas del modelo o request como fallback
        $rawInput = $session->images;

        if (empty($rawInput)) {
            $rawInput = request()->input('images', []);
        }

        if (is_string($rawInput)) {
            $decoded = json_decode($rawInput, true);
            $newPaths = is_array($decoded) ? array_values($decoded) : [];
        } elseif (is_array($rawInput)) {
            $newPaths = array_values($rawInput);
        } else {
            $newPaths = [];
        }

        $oldPaths = $session->getOriginal('images') ?? [];
        $oldPaths = array_values($oldPaths);
        $newPaths = array_values($newPaths);

        $finalPaths = [];

        foreach ($newPaths as $relativePath) {
            if (!str_contains($relativePath, 'backpack/temp/')) {
                $finalPaths[] = $relativePath;
                continue;
            }

            $fullTempPath = storage_path("app/public/{$relativePath}");
            if (!file_exists($fullTempPath)) {
                Log::warning("sessionObserver: No se encontró imagen temporal: {$relativePath}");
                continue;
            }

            try {
                $img = Image::read($fullTempPath);
                if ($img->width() > 1200) {
                    $img = $img->scale(width: 1200);
                }

                $uuid = Str::uuid();
                $filename = "extra-image-{$uuid}.webp";
                $finalPath = $basePath . $filename;

                Storage::disk('public')->put($finalPath, $img->encode(new WebpEncoder(quality: 80)));

                // Versión md
                $mdPath = $basePath . "md-{$filename}";
                $mdImage = Image::read($fullTempPath);
                if ($mdImage->width() > 996) {
                    $mdImage = $mdImage->scale(width: 996);
                }
                Storage::disk('public')->put($mdPath, $mdImage->encode(new WebpEncoder(quality: 80)));

                // Versión sm
                $smPath = $basePath . "sm-{$filename}";
                $smImage = Image::read($fullTempPath);
                if ($smImage->width() > 576) {
                    $smImage = $smImage->scale(width: 576);
                }

                Storage::disk('public')->put($smPath, $smImage->encode(new WebpEncoder(quality: 80)));

                Storage::disk('public')->delete($relativePath);

                $finalPaths[] = $finalPath;
            } catch (\Throwable $e) {
                Log::error("sessionObserver: Error procesando imagen: {$e->getMessage()}");
            }
        }

        // Eliminar imágenes antiguas que ya no están
        $removed = array_diff($oldPaths, $finalPaths);
        foreach ($removed as $removedPath) {
            Storage::disk('public')->delete($removedPath);

            $dir = pathinfo($removedPath, PATHINFO_DIRNAME);
            $file = pathinfo($removedPath, PATHINFO_BASENAME);

            Storage::disk('public')->delete("{$dir}/sm-{$file}");
            Storage::disk('public')->delete("{$dir}/md-{$file}");
        }

        // Guardar si hay cambios
        if ($finalPaths !== $oldPaths) {
            $session->images = $finalPaths;
            $session->saveQuietly();
        }
    }

    /**
     * Handle the Session "deleting" event.
     */
    public function deleting(Session $session)
    {
        if (method_exists($session, 'runSoftDelete')) {
            $session->deleted_by = backpack_user()->id ?? null;
            $session->saveQuietly();
        }
    }

    /**
     * Limpiar completamente toda la cache de la sesión
     */
    private function clearAllSessionCache(Session $session): void
    {
        try {
            // Limpiar todo con tags
            Cache::tags([
                "brand:{$session->brand_id}",
                "session:{$session->id}"
            ])->flush();

            // Limpiar caches específicos adicionales
            $brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';

            // Lista de keys específicas a limpiar
            $specificKeys = [
                "{$brandPrefix}:free:s{$session->id}",
                "{$brandPrefix}:available_web:s{$session->id}",
                "{$brandPrefix}:blocked:s{$session->id}",
                "session_{$session->id}_public_rates_formatted",
                "session_{$session->id}_general_rate",
                "session_{$session->id}_selled_web",
                "session_{$session->id}_selled_all",
                "session_{$session->id}_selled_office",
                "session_{$session->id}_validated_stats"
            ];

            foreach ($specificKeys as $key) {
                Cache::forget($key);
            }

            // Si usas Redis con facade Cache, puedes usar pattern matching así:
            // Nota: Esto depende de tu driver de cache
            if (config('cache.default') === 'redis') {
                try {
                    // Obtener conexión Redis del Cache
                    $redis = Cache::getRedis();

                    // Buscar y eliminar keys con patrón
                    $patterns = [
                        "{$brandPrefix}:config:s{$session->id}:*",
                        "{$brandPrefix}:avail:s{$session->id}:*",
                        "{$brandPrefix}:rates:s{$session->id}:*",
                        "session_{$session->id}_*"
                    ];

                    foreach ($patterns as $pattern) {
                        $keys = $redis->keys($pattern);
                        if (!empty($keys)) {
                            $redis->del($keys);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("SessionObserver: Could not clear Redis pattern keys", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("SessionObserver: Error clearing all cache", [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar archivos de storage de la sesión
     */
    private function deleteSessionFiles(Session $session): void
    {
        try {
            $brand = get_current_brand()->code_name;
            $dir = "uploads/{$brand}/session/{$session->id}";

            if (Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->deleteDirectory($dir);
            }
        } catch (\Exception $e) {
            Log::error("SessionObserver: Error deleting storage files", [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidar caché de eventos cuando cambia una sesión que afecta disponibilidad
     */
    private function invalidateEventListCache(Session $session): void
    {
        try {
            if (!$session->event) {
                return;
            }

            $event = $session->event;
            $brandsToInvalidate = [$event->brand_id];

            // Si el brand tiene padre, también invalidar
            if ($event->brand && $event->brand->parent_id) {
                $brandsToInvalidate[] = $event->brand->parent_id;
            }

            foreach ($brandsToInvalidate as $brandId) {
                Cache::forget("events:all_next:{$brandId}:has_partners:true");
                Cache::forget("events:all_next:{$brandId}:has_partners:false");
            }
        } catch (\Exception $e) {
            Log::error("SessionObserver: Error invalidating event list cache", [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
