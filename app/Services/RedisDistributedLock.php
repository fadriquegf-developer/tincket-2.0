<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Exception;
use Log;

/**
 * Servicio de Lock Distribuido usando Redis
 * Versión simplificada compatible con Predis v3
 */
class RedisDistributedLock
{
    private string $lockKey;
    private string $lockToken;
    private int $ttl;
    private int $retryDelay;
    private int $maxRetries;

    /**
     * @param string $resource Recurso a bloquear (ej: "slot:123")
     * @param int $ttl Tiempo de vida del lock en segundos
     * @param int $retryDelay Milisegundos entre reintentos
     * @param int $maxRetries Número máximo de reintentos
     */
    public function __construct(
        string $resource,
        int $ttl = 10,
        int $retryDelay = 50,
        int $maxRetries = 20
    ) {
        // NO usar prefijo "lock:" para evitar confusión
        $this->lockKey = $resource;
        $this->lockToken = Str::random(32);
        $this->ttl = $ttl;
        $this->retryDelay = $retryDelay;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Intenta adquirir el lock
     * 
     * @return bool True si se adquirió el lock, false si no
     */
    public function acquire(): bool
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            // Usar setnx + expire como fallback confiable
            $acquired = false;

            try {
                // Primero intentar con setnx
                $result = Redis::setnx($this->lockKey, $this->lockToken);

                if ($result == 1 || $result === true) {
                    // Si se estableció, poner TTL
                    Redis::expire($this->lockKey, $this->ttl);
                    $acquired = true;
                }
            } catch (\Exception $e) {
                Log::error('Error acquiring lock', [
                    'key' => $this->lockKey,
                    'error' => $e->getMessage()
                ]);
            }

            if ($acquired) {
                return true;
            }

            $attempts++;

            if ($attempts < $this->maxRetries) {
                // Esperar antes de reintentar (con jitter para evitar thundering herd)
                $jitter = random_int(0, $this->retryDelay / 2);
                usleep(($this->retryDelay + $jitter) * 1000);
            }
        }

        Log::warning('Failed to acquire lock', [
            'key' => $this->lockKey,
            'attempts' => $attempts
        ]);

        return false;
    }

    /**
     * Libera el lock de forma segura
     * Solo libera si el token coincide
     * 
     * @return bool True si se liberó el lock, false si no
     */
    public function release(): bool
    {
        try {
            // Verificar que el token coincida antes de eliminar
            $currentToken = Redis::get($this->lockKey);

            if ($currentToken === $this->lockToken) {
                // Es nuestro lock, eliminarlo
                $result = Redis::del($this->lockKey);

                return ($result > 0);
            } elseif ($currentToken === null) {
                // Ya no existe
                return true;
            } else {
                // Es de otro proceso
                Log::warning('Cannot release lock - token mismatch', [
                    'key' => $this->lockKey,
                    'expected' => $this->lockToken,
                    'current' => $currentToken
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error releasing lock', [
                'key' => $this->lockKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ejecuta un callback con el lock adquirido
     * Libera automáticamente el lock al finalizar
     * 
     * @param callable $callback
     * @return mixed
     * @throws Exception Si no se puede adquirir el lock
     */
    public function execute(callable $callback)
    {
        if (!$this->acquire()) {
            throw new Exception("Could not acquire lock for {$this->lockKey}");
        }

        try {
            return $callback();
        } finally {
            $this->release();
        }
    }

    /**
     * Extiende el TTL del lock si aún lo poseemos
     * 
     * @param int $additionalTtl Segundos adicionales
     * @return bool
     */
    public function extend(int $additionalTtl): bool
    {
        try {
            $currentToken = Redis::get($this->lockKey);

            if ($currentToken === $this->lockToken) {
                Redis::expire($this->lockKey, $this->ttl + $additionalTtl);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error extending lock', [
                'key' => $this->lockKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verifica si el lock está actualmente adquirido por este proceso
     * 
     * @return bool
     */
    public function isHeldByCurrentProcess(): bool
    {
        try {
            $currentToken = Redis::get($this->lockKey);
            return $currentToken === $this->lockToken;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si el recurso está bloqueado (por cualquier proceso)
     * 
     * @return bool
     */
    public function isLocked(): bool
    {
        try {
            $result = Redis::exists($this->lockKey);
            // Predis retorna un entero (0 o 1) para EXISTS
            return ($result == 1);
        } catch (\Exception $e) {
            return false;
        }
    }
}
