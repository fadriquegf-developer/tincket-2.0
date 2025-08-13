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
        $event->saveQuietly();
    }

    public function saved(Event $event)
    {
        $this->processImages($event);
    }

    public static function processImages(Event $event): void
    {
        $brand = get_current_brand()->code_name;
        $eventId = $event->id;
        $basePath = "uploads/{$brand}/event/{$eventId}/";

        // Obtener imágenes nuevas del modelo o request como fallback
        $rawInput = $event->images;

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

        $oldPaths = $event->getOriginal('images') ?? [];
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
                Log::warning("EventObserver: No se encontró imagen temporal: {$relativePath}");
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

                Storage::disk('public')->delete($relativePath);

                $finalPaths[] = $finalPath;
            } catch (\Throwable $e) {
                Log::error("EventObserver: Error procesando imagen: {$e->getMessage()}");
            }
        }

        // Eliminar imágenes antiguas que ya no están
        $removed = array_diff($oldPaths, $finalPaths);
        foreach ($removed as $removedPath) {
            Storage::disk('public')->delete($removedPath);

            $dir  = pathinfo($removedPath, PATHINFO_DIRNAME);
            $file = pathinfo($removedPath, PATHINFO_BASENAME);

            Storage::disk('public')->delete("{$dir}/sm-{$file}");
            Storage::disk('public')->delete("{$dir}/md-{$file}");
        }

        // Guardar si hay cambios
        if ($finalPaths !== $oldPaths) {
            $event->images = $finalPaths;
            $event->saveQuietly();
        }
    }



    public function deleted(Event $event)
    {
        $event->sessions->each(function ($session) {
            $session->delete();
        });

        $brand = get_current_brand()->code_name;
        $dir = "uploads/{$brand}/event/{$event->id}";

        Storage::disk('public')->deleteDirectory($dir);
    }

}
