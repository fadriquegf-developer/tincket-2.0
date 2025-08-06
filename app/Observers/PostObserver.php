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
        \Log::info('[PostObserver] saved ▶️ gallery = ' . json_encode($post->gallery));
        $this->processGalleryImages($post);
    }

    public static function processGalleryImages(Post $post): void
    {
        $brand = get_current_brand()->code_name;
        $postId = $post->id;
        $basePath = "uploads/{$brand}/post/{$postId}/";

        $changes = false;

        // 3) images
        $paths = $post->gallery;

        // Si por alguna razón viene como string JSON, lo convertimos a array
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            $paths = is_array($decoded) ? $decoded : [];
        }

        if (!empty($paths) && is_array($paths)) {
            $finalPaths = [];

            foreach ($paths as $relativePath) {
                try {

                    $fullTempPath = storage_path("app/public/{$relativePath}");
                    if (!file_exists($fullTempPath)) {
                        Log::warning("PostObserver: No se encontró archivo temporal: {$relativePath}");
                        continue;
                    }

                    $img = Image::read($fullTempPath);
                    if ($img->width() > 1200) $img = $img->scale(width: 1200);

                    $newName = 'extra-' . Str::uuid() . '.webp';
                    $finalPath = $basePath . $newName;
                    // => "uploads/brand/session/{id}/extra-uuid.webp"

                    Storage::disk('public')->put($finalPath, $img->encode(new WebpEncoder(quality: 80)));
                    // Borramos el temporal __TEMP__
                    Storage::disk('public')->delete($relativePath);

                    $finalPaths[] = $finalPath;
                } catch (\Throwable $e) {
                    Log::error("Error procesando image extra (Post ID={$postId}): " . $e->getMessage());
                }
            }

            if (!empty($finalPaths)) {
                $post->gallery = $finalPaths;
                $changes = true;
            }
        }

        if ($changes) {
            $post->saveQuietly();
        }
    }
}
