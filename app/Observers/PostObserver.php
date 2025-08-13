<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;

class PostObserver
{
    public function saved(Post $post)
    {
        $this->processGallery($post);
    }

    public static function processGallery(Post $post): void
    {
        $brand = get_current_brand()->code_name;
        $postId = $post->id;
        $basePath = "uploads/{$brand}/post/{$postId}/";

        // 1️ Obtener imágenes nuevas desde el formulario
        $newPaths = request()->has('gallery')
            ? request()->input('gallery')
            : [];

        if (is_string($newPaths)) {
            $decoded = json_decode($newPaths, true);
            $newPaths = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($newPaths)) {
            Log::warning("[PostObserver] gallery inválido en request");
            $newPaths = [];
        }

        $oldPaths = $post->getOriginal('gallery') ?? [];

        $oldPaths = array_values($oldPaths);
        $newPaths = array_values($newPaths);

        $finalPaths = [];

        // 2️ Procesar imágenes nuevas (solo las que vienen de backpack/temp)
        foreach ($newPaths as $relativePath) {
            if (!str_contains($relativePath, 'backpack/temp/')) {
                // Imagen ya procesada anteriormente → conservarla
                $finalPaths[] = $relativePath;
                continue;
            }

            $fullTempPath = storage_path("app/public/{$relativePath}");
            if (!file_exists($fullTempPath)) {
                Log::warning("PostObserver: No se encontró imagen temporal: {$relativePath}");
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
                $mdPath  = $basePath . "md-{$filename}";
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

                // Borrar imagen temporal
                Storage::disk('public')->delete($relativePath);

                $finalPaths[] = $finalPath;
            } catch (\Throwable $e) {
                Log::error("PostObserver: Error procesando imagen: {$e->getMessage()}");
            }
        }

        // 3 Eliminar imágenes que ya no están
         $removed = array_diff($oldPaths, $finalPaths);
        foreach ($removed as $removedPath) {
            Storage::disk('public')->delete($removedPath);

            $dir  = pathinfo($removedPath, PATHINFO_DIRNAME);
            $file = pathinfo($removedPath, PATHINFO_BASENAME);

            Storage::disk('public')->delete("{$dir}/sm-{$file}");
            Storage::disk('public')->delete("{$dir}/md-{$file}");
        }

        // 4️⃣ Guardar cambios solo si el array final ha cambiado
        if ($finalPaths !== $oldPaths) {
            $post->gallery = $finalPaths;
            $post->saveQuietly();
        }
    }

    public function deleted(Post $post)
    {
        $brand = get_current_brand()->code_name;
        $dir = "uploads/{$brand}/post/{$post->id}";

        Storage::disk('public')->deleteDirectory($dir);
    }
}
