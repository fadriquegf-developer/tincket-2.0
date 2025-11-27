<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EncryptTpvSensitiveData extends Migration
{
    private $sensitiveKeys = [
        'sermepaMerchantKey',
        'apiKey',
        'secret',
        'privateKey',
        'token',
        'password',
        'webhookSecret'
    ];

    public function up()
    {
        // Primero añadir los campos de control si no existen
        if (!Schema::hasColumn('tpvs', 'is_active')) {
            Schema::table('tpvs', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('config');
                $table->boolean('is_test_mode')->default(false)->after('is_active');
                $table->boolean('is_default')->default(false)->after('is_test_mode');
                $table->integer('priority')->default(0)->after('is_default');

                $table->index(['brand_id', 'is_active', 'is_default']);
            });
        }

        // Encriptar datos sensibles
        $this->encryptSensitiveData();

        // Marcar TPVs de test
        $this->markTestTpvs();

        // Establecer TPVs por defecto
        $this->setDefaultTpvs();

        // Arreglar TPVs con doble codificación JSON
        $this->fixDoubleEncodedConfigs();
    }

    private function encryptSensitiveData()
    {
        $tpvs = DB::table('tpvs')->get();
        $encrypted = 0;
        $errors = 0;

        foreach ($tpvs as $tpv) {
            try {
                $config = $this->parseConfig($tpv->config);

                if ($config === null) {
                    Log::warning("Migration: TPV {$tpv->id} has invalid config format");
                    $errors++;
                    continue;
                }

                $needsUpdate = false;

                foreach ($config as &$item) {
                    // Skip si ya está encriptado
                    if (isset($item['encrypted']) && $item['encrypted']) {
                        continue;
                    }

                    if (in_array($item['key'], $this->sensitiveKeys)) {
                        $item['value'] = encrypt($item['value']);
                        $item['encrypted'] = true;
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    DB::table('tpvs')
                        ->where('id', $tpv->id)
                        ->update([
                            'config' => json_encode($config),
                            'updated_at' => now()
                        ]);
                    $encrypted++;
                }
            } catch (\Exception $e) {
                Log::error("Migration: Error encrypting TPV {$tpv->id}: " . $e->getMessage());
                $errors++;
            }
        }

        Log::info("Migration: TPV Encryption completed. Encrypted: {$encrypted}, Errors: {$errors}");
    }

    private function markTestTpvs()
    {
        // Marcar por nombre
        DB::table('tpvs')
            ->where(function ($query) {
                $query->where(DB::raw('LOWER(name)'), 'like', '%prueba%')
                    ->orWhere(DB::raw('LOWER(name)'), 'like', '%proves%')
                    ->orWhere(DB::raw('LOWER(name)'), 'like', '%test%')
                    ->orWhere(DB::raw('LOWER(name)'), 'like', '%demo%');
            })
            ->whereNull('deleted_at')
            ->update(['is_test_mode' => true]);

        // Marcar por configuración
        DB::table('tpvs')
            ->where('config', 'like', '%sermepaTestMode%value%1%')
            ->whereNull('deleted_at')
            ->update(['is_test_mode' => true]);

        // Marcar por MerchantKey de prueba conocido
        $testKeys = ['sq7HjrUOBfKmC576ILgskD5srU870gJ7'];
        foreach ($testKeys as $key) {
            DB::table('tpvs')
                ->where('config', 'like', '%' . $key . '%')
                ->whereNull('deleted_at')
                ->update(['is_test_mode' => true]);
        }
    }

    private function setDefaultTpvs()
    {
        // Para cada brand, marcar como default el más usado o el primero
        $brands = DB::table('tpvs')
            ->whereNull('deleted_at')
            ->select('brand_id')
            ->distinct()
            ->pluck('brand_id');

        foreach ($brands as $brandId) {
            // Buscar el TPV más usado en sessions
            $mostUsedTpv = DB::table('sessions')
                ->join('tpvs', 'sessions.tpv_id', '=', 'tpvs.id')
                ->where('tpvs.brand_id', $brandId)
                ->whereNull('sessions.deleted_at')
                ->whereNull('tpvs.deleted_at')
                ->where('tpvs.is_test_mode', false)
                ->select('tpvs.id', DB::raw('COUNT(sessions.id) as usage_count'))
                ->groupBy('tpvs.id')
                ->orderBy('usage_count', 'desc')
                ->first();

            if ($mostUsedTpv) {
                DB::table('tpvs')
                    ->where('id', $mostUsedTpv->id)
                    ->update(['is_default' => true]);
            } else {
                // Si no hay sessions, marcar el primer TPV no-test como default
                $firstTpv = DB::table('tpvs')
                    ->where('brand_id', $brandId)
                    ->whereNull('deleted_at')
                    ->where('is_test_mode', false)
                    ->orderBy('id')
                    ->first();

                if ($firstTpv) {
                    DB::table('tpvs')
                        ->where('id', $firstTpv->id)
                        ->update(['is_default' => true]);
                }
            }
        }
    }

    private function fixDoubleEncodedConfigs()
    {
        $tpvs = DB::table('tpvs')->get();
        $fixed = 0;

        foreach ($tpvs as $tpv) {
            // Detectar doble codificación
            if (is_string($tpv->config) && substr($tpv->config, 0, 2) === '\"') {
                try {
                    // Decodificar el string escapado
                    $decoded = json_decode($tpv->config, true);
                    if (is_string($decoded)) {
                        // Si después de decodificar sigue siendo string, es doble codificación
                        $finalConfig = json_decode($decoded, true);

                        if (is_array($finalConfig)) {
                            DB::table('tpvs')
                                ->where('id', $tpv->id)
                                ->update(['config' => json_encode($finalConfig)]);
                            $fixed++;
                            Log::info("Migration: Fixed double-encoded config for TPV {$tpv->id}");
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Migration: Could not fix TPV {$tpv->id}: " . $e->getMessage());
                }
            }
        }

        if ($fixed > 0) {
            Log::info("Migration: Fixed {$fixed} TPVs with double-encoded configs");
        }
    }

    private function parseConfig($config)
    {
        if (is_array($config)) {
            return $config;
        }

        if (is_string($config)) {
            // Intentar decodificar
            $decoded = json_decode($config, true);

            // Si es doble codificación
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    public function down()
    {
        // Opcionalmente desencriptar (no recomendado en producción)
        Log::warning('TPV encryption rollback is not recommended. Sensitive data will be exposed.');

        // Si realmente necesitas hacer rollback:
        // $this->decryptSensitiveData();

        // Eliminar campos si fueron creados en esta migración
        if (Schema::hasColumn('tpvs', 'is_active')) {
            Schema::table('tpvs', function (Blueprint $table) {
                $table->dropColumn(['is_active', 'is_test_mode', 'is_default', 'priority']);
            });
        }
    }
}
