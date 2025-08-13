<?php

namespace App\Observers;

use App\Models\Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SessionObserver
{
    /**
     * Se dispara cuando se crea una nueva sesión
     */
    public function created(Session $session)
    {
        // Heredar estados de butacas del espacio a la sesión
        $this->inheritSlotsFromSpace($session);
    }

    /**
     * Se dispara después de que un modelo Session se haya guardado (create o update).
     */
    public function saved(Session $session)
    {
        \Log::info('[SessionObserver] saved▶️ images = ' . json_encode($session->images));
        $this->processImages($session);
    }

    /**
     * Hereda los estados de las butacas y zonas del espacio a la nueva sesión
     */
    private function inheritSlotsFromSpace(Session $session)
    {
        // Solo proceder si la sesión es numerada y tiene un espacio asociado
        if (!$session->is_numbered || !$session->space_id) {
            Log::info("[SessionObserver] Session {$session->id} no es numerada o no tiene espacio, saltando herencia de slots");
            return;
        }

        $space = $session->space;
        if (!$space) {
            Log::warning("[SessionObserver] No se encontró el espacio {$session->space_id} para la sesión {$session->id}");
            return;
        }

        Log::info("[SessionObserver] Heredando slots del espacio {$space->id} a la sesión {$session->id}");

        // Obtener todos los slots del espacio con sus estados y zonas
        $slots = $space->slots()
            ->select('id', 'status_id', 'comment', 'zone_id', 'name', 'x', 'y')
            ->get();

        if ($slots->isEmpty()) {
            Log::info("[SessionObserver] El espacio {$space->id} no tiene slots definidos");
            return;
        }

        // Preparar los datos para inserción masiva
        $sessionSlots = [];
        $timestamp = now();

        foreach ($slots as $slot) {
            // Solo crear SessionSlot si el slot tiene un estado definido
            // (Puedes cambiar esta lógica si quieres copiar TODOS los slots)
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
                \DB::table('session_slot')->insert($sessionSlots);
                Log::info("[SessionObserver] Se heredaron " . count($sessionSlots) . " slots con estado del espacio a la sesión {$session->id}");
            } catch (\Exception $e) {
                Log::error("[SessionObserver] Error al heredar slots: " . $e->getMessage());
            }
        } else {
            Log::info("[SessionObserver] No hay slots con estado definido en el espacio {$space->id}");
        }

        // Las zonas ya están disponibles a través de la relación $session->space->zones
        // No necesitan copiarse porque son del espacio, no de la sesión
        Log::info("[SessionObserver] Las zonas del espacio están disponibles a través de session->space->zones");
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

    public function deleting(Session $session)
    {
        if (method_exists($session, 'runSoftDelete')) {
            $session->deleted_by = backpack_user()->id;
            $session->saveQuietly();
        }

        $brand = get_current_brand()->code_name;
        $dir = "uploads/{$brand}/session/{$session->id}";

        Storage::disk('public')->deleteDirectory($dir);
    }
}
