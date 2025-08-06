<?php

namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;


class EventObserver
{

    public function created(Event $event)
    {
        //Al crear, seteamos lo introducido en un idioma en todos los idiomas 'slug', 'name','description'
        $event->setTranslation('name', 'es', $event->name);
        $event->setTranslation('name', 'ca', $event->name);
        $event->setTranslation('name', 'gl', $event->name);
        $event->setTranslation('slug', 'es', $event->slug);
        $event->setTranslation('slug', 'ca', $event->slug);
        $event->setTranslation('slug', 'gl', $event->slug);
        $event->setTranslation('description', 'es', $event->description);
        $event->setTranslation('description', 'ca', $event->description);
        $event->setTranslation('description', 'gl', $event->description);
        $event->save();
    }

    public function saved(Event $event)
    {
        \Log::info('[SessionObserver] saved▶️ images = ' . json_encode($event->images));
        $this->processImages($event);
        $this->processImages($event);
    }

    public static function processImages(Event $event): void
    {
        $brand = get_current_brand()->code_name;
        $eventId = $event->id;
        $basePath = "uploads/{$brand}/event/{$eventId}/";

        $changes = false;

        // 3) images
        $paths = $event->images;

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
                        Log::warning("SessionObserver: No se encontró archivo temporal: {$relativePath}");
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
                    Log::error("Error procesando image extra (Session ID={$eventId}): " . $e->getMessage());
                }
            }

            if (!empty($finalPaths)) {
                $event->images = $finalPaths;
                $changes = true;
            }
        }

        if ($changes) {
            $event->saveQuietly();
        }
    }
    public function deleted(Event $event)
    {
        $event->sessions->each(function ($session) {
            $session->delete();
        });
    }

}
