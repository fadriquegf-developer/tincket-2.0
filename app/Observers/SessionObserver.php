<?php

namespace App\Observers;

use App\Models\Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SessionObserver
{
    /**
     * Se dispara después de que un modelo Session se haya guardado (create o update).
     */
    public function saved(Session $session)
    {
        \Log::info('[SessionObserver] saved▶️ images = ' . json_encode($session->images));
        $this->processImages($session);
    }

    /**
     * Procesa custom_logo, banner e images: redimensiona, convierte a WebP,
     * mueve de __TEMP__ a la carpeta definitiva, borra temporales, y reescribe
     * el campo JSON/array en la base de datos si hay cambios.
     */
    public static function processImages(Session $session): void
    {
        $brand     = get_current_brand()->code_name;
        $sessionId = $session->id;
        $basePath  = "uploads/{$brand}/session/{$sessionId}/";

        $changes = false;

        // 1) custom_logo
        if ($session->custom_logo && Storage::disk('public')->exists($session->custom_logo)) {
            try {
                $newName   = 'custom-logo-' . Str::uuid() . '.webp';
                $finalPath = $basePath . $newName;

                $img = Image::read(Storage::disk('public')->path($session->custom_logo));
                if ($img->width() > 1200)  $img = $img->scale(width: 1200);

                Storage::disk('public')->put($finalPath, $img->encode(new WebpEncoder(quality: 80)));
                Storage::disk('public')->delete($session->custom_logo);

                $session->custom_logo = $finalPath;
                $changes = true;
            } catch (\Throwable $e) {
                Log::error("Error al procesar custom_logo (Session ID={$sessionId}): " . $e->getMessage());
            }
        }

        // 2) banner
        if ($session->banner && Storage::disk('public')->exists($session->banner)) {
            try {
                $newName   = 'banner-' . Str::uuid() . '.webp';
                $finalPath = $basePath . $newName;

                $img = Image::read(Storage::disk('public')->path($session->banner));
                if ($img->width() > 1200)  $img = $img->scale(width: 1200);

                Storage::disk('public')->put($finalPath, $img->encode(new WebpEncoder(quality: 80)));
                Storage::disk('public')->delete($session->banner);

                $session->banner = $finalPath;
                $changes = true;
            } catch (\Throwable $e) {
                Log::error("Error al procesar banner (Session ID={$sessionId}): " . $e->getMessage());
            }
        }

        // 3) images
        $paths = $session->images;
        

        // Si por alguna razón viene como string JSON, lo convertimos a array
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths   = is_array($decoded) ? $decoded : [];
        }
        
        if (!empty($paths) && is_array($paths)) {
            $finalPaths = [];

            foreach ($paths as $relativePath) {
                try {

                    $fullTempPath = storage_path("app/public/{$relativePath}");
                    if (!file_exists($fullTempPath)) {
                        Log::warning("SessionObserver: No se encontró archivo temporal: {$relativePath}");
                        continue;
                    }

                    $img = Image::read($fullTempPath);
                    if ($img->width() > 1200)  $img = $img->scale(width: 1200);

                    $newName   = 'extra-' . Str::uuid() . '.webp';
                    $finalPath = $basePath . $newName; 
                    // => "uploads/brand/session/{id}/extra-uuid.webp"

                    Storage::disk('public')->put($finalPath, $img->encode(new WebpEncoder(quality: 80)));
                    // Borramos el temporal __TEMP__
                    Storage::disk('public')->delete($relativePath);

                    $finalPaths[] = $finalPath;
                } catch (\Throwable $e) {
                    Log::error("Error procesando image extra (Session ID={$sessionId}): " . $e->getMessage());
                }
            }

            if (!empty($finalPaths)) {
                $session->images = $finalPaths;
                $changes = true;
            }
        }

        if ($changes) {
            $session->saveQuietly();
        }
    }

    public function deleting(Session $session)
    {
        if (method_exists($session, 'runSoftDelete')) {
            $session->deleted_by = backpack_user()->id;
            $session->saveQuietly();
        }
    }
}
