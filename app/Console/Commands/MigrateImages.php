<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;
use App\Models\Event;
use App\Models\Brand;

class MigrateImages extends Command
{
    protected $signature = 'migrate:images {brand_id?}';
    protected $description = 'Migra las imágenes de los eventos a la nueva estructura por evento.';

    public function handle()
    {
        $brandId = $this->argument('brand_id');

        $events = Event::when($brandId, fn($q) => $q->where('brand_id', $brandId))->get();

        $this->info("Procesando {$events->count()} eventos...");

        foreach ($events as $event) {
            $brand = Brand::find($event->brand_id);
            if (!$brand)
                continue;

            $basePath = "uploads/{$brand->code_name}/event/{$event->id}/";

            // === LOGO ===
            if ($event->image && is_string($event->image) && trim($event->image) !== '' && Storage::disk('public')->exists($event->image)) {
                $newLogo = $this->migrateImage($event->image, $basePath, 'logo');
                if ($newLogo) {
                    $event->image = $newLogo;
                    $this->line("✓ Logo migrado para event #{$event->id}");
                }
            }

            // === IMÁGENES EXTRAS ===
            $images = is_array($event->images) ? $event->images : json_decode($event->images, true);
            if (is_array($images) && count($images)) {
                $newImages = [];
                foreach ($images as $imgPath) {
                    // Validamos que la ruta no sea vacía, null o inválida
                    if (!$imgPath || $imgPath === '/' || !is_string($imgPath)) {
                        $this->warn("Saltando ruta inválida: " . var_export($imgPath, true));
                        continue;
                    }

                    if (Storage::disk('public')->exists($imgPath)) {
                        $new = $this->migrateImage($imgPath, $basePath, 'extra');
                        if ($new) {
                            $newImages[] = $new;
                        }
                    }
                }

                if ($newImages) {
                    $event->setAttribute('images', $newImages);
                    $this->line("✓ Imágenes extra migradas para event #{$event->id}");
                }
            }

            $event->save();
        }

        $this->info('Migración finalizada.');
    }

    protected function migrateImage(string $oldPath, string $targetDir, string $prefix): ?string
    {
        try {
            // Validación de ruta inválida
            if (!$oldPath || $oldPath === '/' || !is_string($oldPath) || trim($oldPath) === '') {
                $this->warn("Saltando ruta inválida: {$oldPath}");
                return null;
            }

            $imagePath = Storage::disk('public')->path($oldPath);
            $img = Image::read($imagePath);

            $maxWidth = 1200;

            if ($img->width() > $maxWidth) {
                $img = $img->scale(width: $maxWidth);
            }

            $fileName = $prefix . '-' . Str::uuid() . '.webp';
            $finalPath = $targetDir . $fileName;

            Storage::disk('public')->put(
                $finalPath,
                $img->encode(new WebpEncoder(quality: 80))
            );

            Storage::disk('public')->delete($oldPath);

            return $finalPath;
        } catch (\Throwable $e) {
            $this->error("✗ Error migrando {$oldPath}: {$e->getMessage()}");
            return null;
        }
    }

}
