<?php

namespace App\Services;

use App\Models\Session;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\AssignatedRate;
use App\Models\SessionSlot;
use App\Models\SessionTempSlot;
use App\Models\Inscription;
use App\Models\Zone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Servicio unificado y optimizado para gestión de slots con Redis
 */

class RedisSlotsService
{
    private const CACHE_VERSION = 'v3';
    private const TTL_CONFIG = 600;      // 10 minutos
    private const TTL_SLOT = 120;        // 2 minutos
    private const TTL_AVAILABILITY = 3; // 3 segundos
    private const TTL_RATES = 1800;      // 30 minutos
    private const TTL_POSITIONS = 60;    // 1 minuto para posiciones libres
    private const MAX_MEM_CACHE = 100;   // Límite de cache en memoria

    private Session $session;
    private ?string $brandPrefix;
    private bool $showPrivateRates = false;

    private static array $memCache = [];
    private static int $memCacheSize = 0;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->brandPrefix = $session->brand_id ? "b{$session->brand_id}" : 'default';
    }

    /**
     * Obtener configuración completa con optimistic locking
     */
    public function getConfiguration(): array
    {
        if (!$this->session->is_numbered) {
            return $this->getNonNumberedConfiguration();
        }

        $cacheKey = $this->getConfigKey();

        // ✅ Incluir showPrivateRates en la cache key
        if ($this->showPrivateRates) {
            $cacheKey .= ':private';
        }

        $lockKey = "{$cacheKey}:lock";

        $cached = Cache::tags($this->getSessionTags())->get($cacheKey);

        if ($cached && $this->isConfigurationValid($cached)) {
            return $cached;
        }

        $lock = Cache::lock($lockKey, 10);

        if ($lock->get()) {
            try {
                $cached = Cache::tags($this->getSessionTags())->get($cacheKey);
                if ($cached && $this->isConfigurationValid($cached)) {
                    return $cached;
                }

                $configuration = $this->buildOptimizedConfiguration();

                Cache::tags($this->getSessionTags())
                    ->put($cacheKey, $configuration, self::TTL_CONFIG);

                $this->prewarmRelatedCaches($configuration);

                return $configuration;
            } finally {
                $lock->release();
            }
        }

        return $cached ?: $this->buildOptimizedConfiguration();
    }

    /**
     * Verificar disponibilidad de slot con cache multinivel
     */
    public function isSlotAvailable(int $slotId, bool $isTicketOffice = false, bool $isForPack = false): bool
    {
        // L1: Cache en memoria con límite y limpieza automática
        $memKey = "{$this->session->id}:{$slotId}:{$isTicketOffice}:{$isForPack}";

        // Verificar si necesitamos limpiar el cache
        if (self::$memCacheSize >= self::MAX_MEM_CACHE) {
            $this->cleanOldestMemCacheEntries();
        }

        if (isset(self::$memCache[$memKey])) {
            // Actualizar timestamp de último acceso
            self::$memCache[$memKey]['accessed'] = microtime(true);
            return self::$memCache[$memKey]['value'];
        }

        // L2: Redis cache
        $cacheKey = $this->getAvailabilityKey($slotId, $isTicketOffice, $isForPack);

        $result = Cache::tags($this->getSlotTags($slotId))
            ->remember($cacheKey, self::TTL_AVAILABILITY, function () use ($slotId, $isTicketOffice, $isForPack) {
                return $this->calculateAvailability($slotId, $isTicketOffice, $isForPack);
            });

        // Guardar en memoria con timestamp
        self::$memCache[$memKey] = [
            'value' => $result,
            'created' => microtime(true),
            'accessed' => microtime(true)
        ];
        self::$memCacheSize++;

        return $result;
    }

    /**
     * Limpiar las entradas más antiguas del cache en memoria
     */
    private function cleanOldestMemCacheEntries(): void
    {
        if (empty(self::$memCache)) {
            self::$memCacheSize = 0;
            return;
        }

        // Ordenar por último acceso (LRU - Least Recently Used)
        uasort(self::$memCache, function ($a, $b) {
            return $a['accessed'] <=> $b['accessed'];
        });

        // Eliminar el 30% más antiguo
        $toRemove = (int) (self::MAX_MEM_CACHE * 0.3);
        $removed = 0;

        foreach (self::$memCache as $key => $value) {
            unset(self::$memCache[$key]);
            $removed++;
            if ($removed >= $toRemove) {
                break;
            }
        }

        self::$memCacheSize = count(self::$memCache);
    }

    /**
     * Limpiar cache en memoria manualmente
     */
    public static function clearMemoryCache(): void
    {
        self::$memCache = [];
        self::$memCacheSize = 0;
    }

    /**
     * Limpiar entradas antiguas del cache en memoria (más de X segundos)
     */
    public static function cleanExpiredMemCache(int $maxAgeSeconds = 120): int
    {
        if (empty(self::$memCache)) {
            return 0;
        }

        $now = microtime(true);
        $expired = 0;

        foreach (self::$memCache as $key => $entry) {
            $age = $now - $entry['created'];
            if ($age > $maxAgeSeconds) {
                unset(self::$memCache[$key]);
                $expired++;
            }
        }

        self::$memCacheSize = count(self::$memCache);

        return $expired;
    }

    /**
     * Obtener estadísticas del cache en memoria
     */
    public function getMemoryCacheStats(): array
    {
        $stats = [
            'size' => self::$memCacheSize,
            'max_size' => self::MAX_MEM_CACHE,
            'usage_percent' => round((self::$memCacheSize / self::MAX_MEM_CACHE) * 100, 2)
        ];

        if (!empty(self::$memCache)) {
            $now = microtime(true);
            $ages = [];

            foreach (self::$memCache as $entry) {
                $ages[] = $now - $entry['created'];
            }

            $stats['oldest_entry_seconds'] = max($ages);
            $stats['newest_entry_seconds'] = min($ages);
            $stats['avg_age_seconds'] = round(array_sum($ages) / count($ages), 2);
        }

        return $stats;
    }

    /**
     * Calcular disponibilidad real del slot
     */
    private function calculateAvailability(int $slotId, bool $isTicketOffice, bool $isForPack): bool
    {
        $excludedStatuses = $this->getExcludedStatuses($isTicketOffice, $isForPack);

        $result = DB::selectOne("
            SELECT CASE 
                WHEN ss.status_id IS NOT NULL AND ss.status_id IN (" . implode(',', $excludedStatuses) . ") THEN 0
                WHEN EXISTS (
                    SELECT 1 FROM inscriptions i
                    INNER JOIN carts c ON c.id = i.cart_id
                    WHERE i.slot_id = ?
                        AND i.session_id = ?
                        AND i.deleted_at IS NULL
                        AND (c.confirmation_code IS NOT NULL OR c.expires_on > NOW())
                ) THEN 0
                WHEN EXISTS (
                    SELECT 1 FROM session_temp_slot sts
                    WHERE sts.slot_id = ?
                        AND sts.session_id = ?
                        AND sts.expires_on > NOW()
                        AND sts.deleted_at IS NULL
                ) THEN 0
                ELSE 1
            END as is_available
            FROM slots s
            LEFT JOIN session_slot ss ON ss.slot_id = s.id AND ss.session_id = ?
            WHERE s.id = ?
        ", [
            $slotId,
            $this->session->id,
            $slotId,
            $this->session->id,
            $this->session->id,
            $slotId
        ]);

        return (bool) ($result->is_available ?? false);
    }

    /**
     * Verificación masiva optimizada con pipeline
     */
    public function checkBulkAvailability(array $slotIds, bool $isTicketOffice = false, bool $isForPack = false): array
    {
        if (empty($slotIds)) {
            return [];
        }

        // Para bulk, siempre usar query directa para frescura
        return $this->calculateBulkAvailabilityOptimized($slotIds, $isTicketOffice, $isForPack);
    }

    /**
     * Actualizar estado de slot con invalidación inteligente
     */
    public function updateSlotState(int $slotId, array $data): void
    {
        DB::transaction(function () use ($slotId, $data) {
            // Update in database if needed
            if (isset($data['status_id'])) {
                SessionSlot::updateOrCreate(
                    [
                        'session_id' => $this->session->id,
                        'slot_id' => $slotId
                    ],
                    [
                        'status_id' => $data['status_id'],
                        'comment' => $data['comment'] ?? null
                    ]
                );
            }

            // Invalidate only affected caches
            $this->invalidateSlotCaches($slotId);

            // Queue async configuration rebuild
            dispatch(function () {
                $this->regenerateConfiguration();
            })->afterResponse();
        });
    }

    /**
     * Construcción optimizada de configuración
     */
    private function buildOptimizedConfiguration(): array
    {
        $startTime = microtime(true);

        // Single optimized query with all needed data
        $slots = $this->fetchAllSlotsOptimized();

        // Group by zones efficiently
        $zones = [];
        $zoneCache = [];

        foreach ($slots as $slot) {
            $zoneId = $slot->zone_id;

            if (!isset($zones[$zoneId])) {
                $zones[$zoneId] = [
                    'id' => $zoneId,
                    'name' => $slot->zone_name ?? 'Zona',
                    'color' => $slot->zone_color ?? '#000000',
                    'slots' => [],
                    'rates' => $this->getZoneRatesCached($zoneId)
                ];
            }

            $zones[$zoneId]['slots'][] = $this->formatSlotOptimized($slot, $zones[$zoneId]['rates']);
        }

        $configuration = [
            'session_id' => $this->session->id,
            'space_id' => $this->session->space_id,
            'space_name' => $this->session->space->name ?? '',
            'numbered' => true,
            'zones' => array_values($zones),
            'stats' => [
                'total_slots' => count($slots),
                'free_positions' => $this->getFreePositions(),
                'blocked_positions' => $this->countBlockedInscriptions()
            ],
            'cached_at' => now()->toIso8601String(),
            'build_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'version' => self::CACHE_VERSION
        ];

        return $configuration;
    }

    /**
     * Query ultra-optimizada para obtener todos los slots
     */
    private function fetchAllSlotsOptimized()
    {
        return DB::select("
            WITH active_inscriptions AS (
                SELECT 
                    i.slot_id,
                    i.session_id,
                    MAX(c.id) as cart_id
                FROM inscriptions i
                INNER JOIN carts c ON c.id = i.cart_id
                WHERE i.session_id = ?
                    AND i.deleted_at IS NULL
                    AND c.deleted_at IS NULL
                    AND (c.confirmation_code IS NOT NULL OR c.expires_on > NOW())
                GROUP BY i.slot_id, i.session_id
            ),
            active_temp_slots AS (
                SELECT 
                    slot_id,
                    session_id,
                    MAX(status_id) as status_id
                FROM session_temp_slot
                WHERE session_id = ?
                    AND expires_on > NOW()
                    AND deleted_at IS NULL
                GROUP BY slot_id, session_id
            )
            SELECT 
                s.id,
                s.name,
                s.x,
                s.y,
                s.zone_id,
                z.name as zone_name,
                z.color as zone_color,
                CASE 
                    WHEN ai.slot_id IS NOT NULL THEN 1
                    WHEN ss.status_id IS NOT NULL AND ss.status_id != 6 THEN 1
                    WHEN ats.slot_id IS NOT NULL THEN 1
                    ELSE 0
                END as is_locked,
                COALESCE(
                    CASE WHEN ai.slot_id IS NOT NULL THEN 2 END,
                    ss.status_id,
                    ats.status_id
                ) as lock_reason,
                ss.comment,
                ai.cart_id
            FROM slots s
            LEFT JOIN zones z ON z.id = s.zone_id
            LEFT JOIN session_slot ss ON ss.slot_id = s.id AND ss.session_id = ?
            LEFT JOIN active_inscriptions ai ON ai.slot_id = s.id
            LEFT JOIN active_temp_slots ats ON ats.slot_id = s.id
            WHERE s.space_id = ?
            ORDER BY s.zone_id, s.y, s.x
        ", [
            $this->session->id,
            $this->session->id,
            $this->session->id,
            $this->session->space_id
        ]);
    }

    /**
     * Cálculo optimizado de disponibilidad masiva
     */
    private function calculateBulkAvailabilityOptimized(array $slotIds, bool $isTicketOffice, bool $isForPack): array
    {
        $excludedStatuses = $this->getExcludedStatuses($isTicketOffice, $isForPack);
        $placeholders = implode(',', array_fill(0, count($slotIds), '?'));

        $results = DB::select("
            WITH slot_status AS (
                SELECT 
                    s.id,
                    CASE 
                        WHEN ss.status_id IN (" . implode(',', $excludedStatuses) . ") 
                            AND ss.status_id IS NOT NULL THEN 0
                        WHEN EXISTS (
                            SELECT 1 FROM inscriptions i
                            INNER JOIN carts c ON c.id = i.cart_id
                            WHERE i.slot_id = s.id
                                AND i.session_id = ?
                                AND i.deleted_at IS NULL
                                AND (c.confirmation_code IS NOT NULL OR c.expires_on > NOW())
                        ) THEN 0
                        WHEN EXISTS (
                            SELECT 1 FROM session_temp_slot sts
                            WHERE sts.slot_id = s.id
                                AND sts.session_id = ?
                                AND sts.expires_on > NOW()
                                AND sts.deleted_at IS NULL
                        ) THEN 0
                        ELSE 1
                    END as is_available
                FROM slots s
                LEFT JOIN session_slot ss ON ss.slot_id = s.id AND ss.session_id = ?
                WHERE s.id IN ($placeholders)
            )
            SELECT id, is_available FROM slot_status
        ", array_merge(
            [$this->session->id, $this->session->id, $this->session->id],
            $slotIds
        ));

        return collect($results)->pluck('is_available', 'id')->toArray();
    }

    /**
     * Pre-calentar caches relacionadas
     */
    private function prewarmRelatedCaches(array $configuration): void
    {
        // Pre-warm zone rates
        foreach ($configuration['zones'] as $zone) {
            $this->getZoneRatesCached($zone['id']);
        }

        // Pre-warm availability for visible slots (first 100)
        $visibleSlotIds = collect($configuration['zones'])
            ->pluck('slots')
            ->flatten(1)
            ->take(100)
            ->pluck('id')
            ->toArray();

        if (!empty($visibleSlotIds)) {
            $this->checkBulkAvailability($visibleSlotIds);
        }
    }

    /**
     * Invalidación inteligente de caches
     */
    private function invalidateSlotCaches(int $slotId): void
    {
        // Invalidate specific slot
        Cache::tags($this->getSlotTags($slotId))->flush();

        // Mark configuration as stale but don't delete
        $configKey = $this->getConfigKey();
        $config = Cache::tags($this->getSessionTags())->get($configKey);

        if ($config) {
            $config['stale'] = true;
            Cache::tags($this->getSessionTags())->put($configKey, $config, 30);
        }
    }

    /**
     * Regeneración asíncrona de configuración
     */
    public function regenerateConfiguration(): void
    {
        $lockKey = "regen:{$this->brandPrefix}:s{$this->session->id}";
        $lock = Cache::lock($lockKey, 60);

        if (!$lock->get()) {
            return;
        }

        try {
            $configuration = $this->buildOptimizedConfiguration();

            Cache::tags($this->getSessionTags())
                ->put($this->getConfigKey(), $configuration, self::TTL_CONFIG);
        } finally {
            $lock->release();
        }
    }

    /**
     * Liberar slots expirados con batch processing
     */
    public function freeExpiredSlots(): int
    {
        $freedCount = 0;

        DB::transaction(function () use (&$freedCount) {
            // Batch delete expired temp slots
            $deleted = SessionTempSlot::where('session_id', $this->session->id)
                ->where('expires_on', '<', now())
                ->delete();

            $freedCount += $deleted;

            // Find expired cart inscriptions
            $expiredSlotIds = Inscription::where('session_id', $this->session->id)
                ->whereNotNull('slot_id')
                ->whereHas('cart', function ($q) {
                    $q->whereNull('confirmation_code')
                        ->where('expires_on', '<', now());
                })
                ->pluck('slot_id')
                ->unique()
                ->toArray();

            if (!empty($expiredSlotIds)) {
                foreach ($expiredSlotIds as $slotId) {
                    $this->invalidateSlotCaches($slotId);
                }
                $freedCount += count($expiredSlotIds);
            }
        });

        if ($freedCount > 0) {
            $this->regenerateConfiguration();
        }

        return $freedCount;
    }

    // ==================== HELPER METHODS ====================

    private function getExcludedStatuses(bool $isTicketOffice, bool $isForPack): array
    {
        if ($isTicketOffice) {
            // En taquilla, SOLO estos estados son clicables:
            // 3 = Reservado (VIP)
            // 6 = Movilidad reducida  
            // 7 = Movilidad reducida (otro tipo)
            // 8 = Reserva abonament
            $allowedInTicketOffice = [3, 6, 7, 8];

            // Todos los demás quedan bloqueados
            // Devolver array de TODOS los status_id que NO están en la lista permitida
            // Asumiendo que tienes status_id del 1 al 8 (ajustar si se añaden nuevos estados)
            $allStatuses = range(1, 8);
            return array_diff($allStatuses, $allowedInTicketOffice);
        }

        // Para WEB público
        // Bloquear: 1(bloqueado), 2(vendido), 3(reservado), 4(oculto), 5(hidden), 7(reducida), 8(abonament)
        // Solo 6 (movilidad reducida) es visible en web
        return [1, 2, 3, 4, 5, 7, 8];
    }

    private function formatSlotOptimized($slot, array $zoneRates): array
    {
        return [
            'id' => (int) $slot->id,
            'name' => $slot->name,
            'x' => (int) $slot->x,
            'y' => (int) $slot->y,
            'zone_id' => (int) $slot->zone_id,
            'is_locked' => (bool) $slot->is_locked,
            'lock_reason' => $slot->lock_reason,
            'comment' => $slot->comment,
            'cart_id' => $slot->cart_id,
            'rates' => $zoneRates // Usar rates de zona cacheadas
        ];
    }

    private function getZoneRatesCached(?int $zoneId): array
    {
        if (!$zoneId) {
            return [];
        }

        $locale = app()->getLocale();
        $cacheKey = "{$this->brandPrefix}:rates:s{$this->session->id}:z{$zoneId}:{$locale}";

        // Incluir showPrivateRates en la cache key
        if ($this->showPrivateRates) {
            $cacheKey .= ':private';
        }

        return Cache::remember($cacheKey, self::TTL_RATES, function () use ($zoneId) {
            // Cargar AssignatedRate sin BrandScope
            $allRates = AssignatedRate::withoutGlobalScope(\App\Scopes\BrandScope::class)
                ->where('session_id', $this->session->id)
                ->where('assignated_rate_type', Zone::class)
                ->where('assignated_rate_id', $zoneId)
                ->with(['rate' => function ($q) {
                    $q->withoutGlobalScope(\App\Scopes\BrandScope::class)
                        ->select('id', 'name', 'needs_code', 'has_rule', 'rule_parameters');
                }])
                ->get();

            return $allRates
                // Filtrar rates que no tengan la relación cargada
                ->filter(function ($ar) {
                    if ($ar->rate === null) {
                        Log::warning('⚠️ AssignatedRate sin Rate', [
                            'assignated_rate_id' => $ar->id,
                            'rate_id' => $ar->rate_id,
                            'session_id' => $this->session->id
                        ]);
                        return false;
                    }

                    // ✅ CRÍTICO: Filtrar rates no públicas cuando showPrivateRates = false
                    // Usar comparación flexible (truthy) en lugar de estricta
                    if (!$this->showPrivateRates) {
                        // Solo incluir rates con is_public truthy (1, true, "1")
                        if (!($ar->is_public ?? false)) {
                            return false;
                        }
                    }

                    return true;
                })
                ->map(fn($ar) => [
                    'id' => $ar->rate->id,
                    'name' => $ar->rate->name,
                    'price' => $ar->price,
                    'is_public' => $ar->is_public,
                    'is_private' => $ar->is_private,
                    'max_on_sale' => $ar->max_on_sale,
                    'max_per_order' => $ar->max_per_order,
                    'max_per_code' => $ar->max_per_code,
                    'needs_code' => $ar->rate->needs_code ?? false,
                    'has_rule' => $ar->rate->has_rule ?? false,
                    'rule_parameters' => $ar->rate->rule_parameters ?? null,
                ])
                ->values() // Re-indexar después del filter
                ->toArray();
        });
    }

    private function isConfigurationValid($config): bool
    {
        if (!is_array($config)) {
            return false;
        }

        // Check if stale
        if (!empty($config['stale'])) {
            return false;
        }

        // Check version
        if (($config['version'] ?? '') !== self::CACHE_VERSION) {
            return false;
        }

        // Check age (optional - for extra freshness)
        if (isset($config['cached_at'])) {
            $age = now()->diffInSeconds($config['cached_at']);
            if ($age > self::TTL_CONFIG) {
                return false;
            }
        }

        return true;
    }

    private function getNonNumberedConfiguration(): array
    {
        return [
            'session_id' => $this->session->id,
            'space_id' => $this->session->space_id,
            'space_name' => $this->session->space->name ?? '',
            'numbered' => false,
            'zones' => [],
            'stats' => [
                'total_slots' => 0,
                'free_positions' => $this->getFreePositions(),
                'blocked_positions' => 0
            ],
            'cached_at' => now()->toIso8601String(),
            'version' => self::CACHE_VERSION
        ];
    }

    // ==================== CACHE KEYS ====================

    private function getConfigKey(): string
    {
        $locale = app()->getLocale();
        return "{$this->brandPrefix}:config:s{$this->session->id}:v" . self::CACHE_VERSION . ":{$locale}";
    }

    private function getAvailabilityKey(int $slotId, bool $isTicketOffice, bool $isForPack): string
    {
        $flags = ($isTicketOffice ? 't' : '') . ($isForPack ? 'p' : '');
        return "{$this->brandPrefix}:avail:s{$this->session->id}:sl{$slotId}:{$flags}";
    }

    private function getSessionTags(): array
    {
        return [
            "brand:{$this->session->brand_id}",
            "session:{$this->session->id}"
        ];
    }

    private function getSlotTags(int $slotId): array
    {
        return [
            "brand:{$this->session->brand_id}",
            "session:{$this->session->id}",
            "slot:{$slotId}"
        ];
    }

    // ==================== PUBLIC HELPER METHODS ====================

    /**
     * Método para obtener estadísticas de performance
     */
    public function getPerformanceStats(): array
    {
        $config = Cache::tags($this->getSessionTags())->get($this->getConfigKey());

        return [
            'cached' => !empty($config),
            'build_time_ms' => $config['build_time_ms'] ?? null,
            'age_seconds' => isset($config['cached_at'])
                ? now()->diffInSeconds($config['cached_at'])
                : null,
            'stale' => !empty($config['stale']),
            'total_slots' => $config['stats']['total_slots'] ?? 0
        ];
    }

    /**
     * Limpiar toda la cache de la sesión
     */
    public function clearAllCache(): void
    {
        Cache::tags($this->getSessionTags())->flush();
    }

    /**
     * Método singleton para obtener instancia
     */
    public static function for(Session $session): self
    {
        static $instances = [];

        $key = $session->id;

        if (!isset($instances[$key])) {
            $instances[$key] = new self($session);
        }

        return $instances[$key];
    }

    /**
     * Liberar un slot específico
     */
    public function freeSlot(int $slotId): void
    {
        // Invalidar caches del slot
        $this->invalidateSlotCaches($slotId);
    }

    /**
     * Bloquear un slot
     */
    public function lockSlot(int $slotId, int $reason = 2, ?string $comment = null, ?int $cartId = null): void
    {
        $this->updateSlotState($slotId, [
            'is_locked' => true,
            'lock_reason' => $reason,
            'comment' => $comment,
            'cart_id' => $cartId
        ]);
    }

    /**
     * Obtener posiciones libres de la sesión
     */
    public function getFreePositions(): int
    {
        $cacheKey = "{$this->brandPrefix}:free:s{$this->session->id}";

        return Cache::remember($cacheKey, self::TTL_POSITIONS, function () {
            return DB::transaction(function () {
                return $this->calculateFreePositions();
            }, 3);
        });
    }

    /**
     * Calcular posiciones libres reales
     */
    private function calculateFreePositions(): int
    {
        $maxPlaces = $this->session->max_places ?? 0;
        $cartTTL = $this->session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

        // Query optimizada unificada
        $blockedData = DB::table('inscriptions')
            ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
            ->where('inscriptions.session_id', $this->session->id)
            ->whereNull('inscriptions.deleted_at')
            ->selectRaw('
                COUNT(CASE WHEN carts.confirmation_code IS NOT NULL THEN 1 END) as confirmed,
                COUNT(CASE 
                    WHEN carts.confirmation_code IS NULL 
                    AND carts.expires_on > ? THEN 1 
                END) as pending
            ', [now()->subMinutes($cartTTL)])
            ->first();

        $blocked = $blockedData->confirmed + $blockedData->pending;

        // Calcular autolock si aplica
        $autolock = 0;
        if ($this->session->autolock_type !== null) {
            $autolock = DB::table('session_temp_slot')
                ->where('session_id', $this->session->id)
                ->where(function ($q) {
                    $q->where('expires_on', '>', now())
                        ->orWhereNull('expires_on');
                })
                ->distinct('slot_id')
                ->count('slot_id');
        }

        $limitX100 = $this->session->limit_x_100 ?? 100;
        $limit = round($maxPlaces * ($limitX100 / 100));

        $realCapacity = $maxPlaces - $blocked - $autolock;
        $freeWithLimit = $limit - $blocked;

        return max(min($realCapacity, $freeWithLimit), 0);
    }

    /**
     * Obtener posiciones disponibles para venta web
     */
    public function getAvailableWebPositions(): int
    {
        $cacheKey = "{$this->brandPrefix}:available_web:s{$this->session->id}";

        return Cache::remember($cacheKey, self::TTL_POSITIONS, function () {
            return DB::transaction(function () {
                return $this->calculateAvailableWebPositions();
            }, 3);
        });
    }

    /**
     * Calcular posiciones disponibles para web
     */
    private function calculateAvailableWebPositions(): int
    {
        $maxPlaces = $this->session->max_places ?? 0;
        $cartTTL = $this->session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);
        $limitX100 = $this->session->limit_x_100 ?? 100;
        $limit = round($maxPlaces * ($limitX100 / 100));

        // Stats de inscripciones
        $stats = DB::table('inscriptions as i')
            ->join('carts as c', 'c.id', '=', 'i.cart_id')
            ->where('i.session_id', $this->session->id)
            ->whereNull('i.deleted_at')
            ->selectRaw('
                COUNT(DISTINCT CASE 
                    WHEN c.confirmation_code IS NOT NULL 
                    THEN i.id 
                END) as confirmed_inscriptions,
                COUNT(DISTINCT CASE 
                    WHEN c.confirmation_code IS NULL 
                    AND c.expires_on > ? 
                    THEN i.id 
                END) as pending_inscriptions
            ', [now()->subMinutes($cartTTL)])
            ->first();

        // Slots bloqueados
        $blockedSlots = DB::table('session_slot')
            ->where('session_id', $this->session->id)
            ->whereNotIn('status_id', [2, 6])
            ->count();

        // Inscripciones en slots bloqueados
        $soldInBlockedSlots = DB::table('inscriptions as i')
            ->join('carts as c', 'c.id', '=', 'i.cart_id')
            ->join('session_slot as ss', function ($join) {
                $join->on('ss.slot_id', '=', 'i.slot_id')
                    ->where('ss.session_id', '=', $this->session->id);
            })
            ->where('i.session_id', $this->session->id)
            ->whereNotNull('c.confirmation_code')
            ->whereNotIn('ss.status_id', [2, 6])
            ->count();

        $effectiveBlockedSlots = max(0, $blockedSlots - $soldInBlockedSlots);

        // Slots vendidos
        $sellStatusSlots = DB::table('session_slot')
            ->where('session_id', $this->session->id)
            ->where('status_id', 2)
            ->count();

        $extraSellSlots = max(0, $sellStatusSlots - $stats->confirmed_inscriptions);

        // Disponibilidad por tarifas públicas
        $availableByRates = $this->calculateAvailableByPublicRates();

        // Autolock
        $autolock = 0;
        if ($this->session->autolock_type !== null) {
            $autolock = DB::table('session_temp_slot')
                ->where('session_id', $this->session->id)
                ->where(function ($q) {
                    $q->where('expires_on', '>', now())
                        ->orWhereNull('expires_on');
                })
                ->distinct('slot_id')
                ->count('slot_id');
        }

        $totalBlocked = $stats->confirmed_inscriptions + $stats->pending_inscriptions;
        $realCapacity = $maxPlaces - $totalBlocked - $autolock;
        $freeWithLimit = $limit - $totalBlocked;
        $sessionSlotBlocked = $maxPlaces - $effectiveBlockedSlots - $totalBlocked - $extraSellSlots;

        return max(min($realCapacity, $freeWithLimit, $availableByRates, $sessionSlotBlocked), 0);
    }

    /**
     * Calcular disponibilidad por tarifas públicas
     */
    private function calculateAvailableByPublicRates(): int
    {
        $cartTTL = $this->session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

        $publicRates = DB::table('assignated_rates')
            ->where('session_id', $this->session->id)
            ->where('is_public', true)
            ->get();

        $totalAvailable = 0;

        foreach ($publicRates as $rate) {
            $used = DB::table('inscriptions as i')
                ->join('carts as c', 'c.id', '=', 'i.cart_id')
                ->where('i.session_id', $this->session->id)
                ->where('i.rate_id', $rate->rate_id)
                ->whereNull('i.deleted_at')
                ->where(function ($q) use ($cartTTL) {
                    $q->whereNotNull('c.confirmation_code')
                        ->orWhere(function ($sq) use ($cartTTL) {
                            $sq->whereNull('c.confirmation_code')
                                ->where('c.expires_on', '>', now()->subMinutes($cartTTL));
                        });
                })
                ->count();

            $tempSlotUsed = DB::table('inscriptions as i')
                ->join('session_temp_slot as sts', 'sts.inscription_id', '=', 'i.id')
                ->where('i.session_id', $this->session->id)
                ->where('i.rate_id', $rate->rate_id)
                ->where('sts.expires_on', '>', now()->subMinutes($cartTTL))
                ->whereNull('sts.deleted_at')
                ->count();

            $available = max(0, $rate->max_on_sale - $used - $tempSlotUsed);
            $totalAvailable += $available;
        }

        return $totalAvailable;
    }

    /**
     * Contar inscripciones bloqueadas
     */
    public function countBlockedInscriptions(): int
    {
        $cacheKey = "{$this->brandPrefix}:blocked:s{$this->session->id}";

        return Cache::remember($cacheKey, self::TTL_POSITIONS, function () {
            $cartTTL = $this->session->brand->getSetting(Brand::EXTRA_CONFIG['CART_TTL_KEY'], Cart::DEFAULT_MINUTES_TO_EXPIRE);

            return DB::table('inscriptions')
                ->join('carts', 'carts.id', '=', 'inscriptions.cart_id')
                ->where('inscriptions.session_id', $this->session->id)
                ->whereNull('inscriptions.deleted_at')
                ->where(function ($q) use ($cartTTL) {
                    $q->whereNotNull('carts.confirmation_code')
                        ->orWhere(function ($subQ) use ($cartTTL) {
                            $subQ->whereNull('carts.confirmation_code')
                                ->where('carts.expires_on', '>', now()->subMinutes($cartTTL));
                        });
                })
                ->count();
        });
    }

    /**
     * Invalidar todos los caches de disponibilidad
     */
    public function invalidateAvailabilityCache(): void
    {
        Cache::forget("{$this->brandPrefix}:free:s{$this->session->id}");
        Cache::forget("{$this->brandPrefix}:available_web:s{$this->session->id}");
        Cache::forget("{$this->brandPrefix}:blocked:s{$this->session->id}");

        // También invalidar cache de configuración
        Cache::tags($this->getSessionTags())->flush();
    }

    /**
     * Regenerar todos los caches
     */
    public function regenerateCache(): void
    {
        $this->invalidateAvailabilityCache();
        $this->regenerateConfiguration();
    }

    /**
     * Verificar si está cacheado (compatibilidad con SlotCacheService)
     */
    public function isCached(): bool
    {
        $config = Cache::tags($this->getSessionTags())->get($this->getConfigKey());
        return !empty($config) && $this->isConfigurationValid($config);
    }

    /**
     * Configurar si mostrar tarifas privadas
     */
    public function setShowPrivateRates(bool $show): self
    {
        $this->showPrivateRates = $show;
        // Invalidar cache de tarifas al cambiar configuración
        Cache::forget("{$this->brandPrefix}:rates:s{$this->session->id}:*");
        return $this;
    }

    /**
     * Obtener estado de todos los slots (compatibilidad con SlotCacheService)
     */
    public function getSlotsState(): ?array
    {
        if (!$this->session->is_numbered) {
            return null;
        }

        $configuration = $this->getConfiguration();

        // Formatear respuesta para compatibilidad
        $result = [];
        foreach ($configuration['zones'] as $zone) {
            $result[] = [
                'zone' => (object) [
                    'id' => $zone['id'],
                    'name' => $zone['name'],
                    'color' => $zone['color']
                ],
                'slots' => collect($zone['slots'])->map(function ($slot) {
                    return (object) [
                        'id' => $slot['id'],
                        'zone_id' => $slot['zone_id'],
                        'lock_reason' => $slot['lock_reason'],
                        'rates' => $slot['rates'],
                        'is_locked' => $slot['is_locked'],
                        'comment' => $slot['comment'],
                        'name' => $slot['name'],
                        'x' => $slot['x'],
                        'y' => $slot['y']
                    ];
                })
            ];
        }

        return $result;
    }
}
