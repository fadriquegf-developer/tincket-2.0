<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\PngEncoder;
use App\Models\Event;
use App\Models\Session;
use App\Models\Brand;

class ConvertWebpToPng extends Command
{
    protected $signature = 'images:webp-to-png 
                            {--dry-run : Mostrar qué se haría sin ejecutar cambios}
                            {--keep-webp : Mantener los archivos webp originales}
                            {--model= : Procesar solo un modelo (event, session o brand)}
                            {--id= : Procesar solo un ID específico}';

    protected $description = 'Convierte imágenes webp de logo, custom_logo y banner a PNG para compatibilidad con PDFs';

    private int $converted = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private bool $dryRun = false;
    private bool $keepWebp = false;

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->keepWebp = $this->option('keep-webp');
        $modelFilter = $this->option('model');
        $idFilter = $this->option('id');

        if ($this->dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se realizarán cambios');
            $this->newLine();
        }

        // Procesar Brands
        if (!$modelFilter || $modelFilter === 'brand') {
            $this->info('📦 Procesando BRANDS...');
            $this->processBrands($idFilter);
            $this->newLine();
        }

        // Procesar Eventos
        if (!$modelFilter || $modelFilter === 'event') {
            $this->info('📦 Procesando EVENTOS...');
            $this->processModel(Event::class, 'events', ['custom_logo', 'banner'], $idFilter);
            $this->newLine();
        }

        // Procesar Sesiones
        if (!$modelFilter || $modelFilter === 'session') {
            $this->info('📦 Procesando SESIONES...');
            $this->processModel(Session::class, 'sessions', ['custom_logo', 'banner'], $idFilter);
            $this->newLine();
        }

        // Resumen
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('📊 RESUMEN:');
        $this->info("   ✅ Convertidas: {$this->converted}");
        $this->info("   ⏭️  Omitidas:    {$this->skipped}");
        $this->info("   ❌ Errores:     {$this->errors}");
        $this->info('═══════════════════════════════════════');

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('💡 Ejecuta sin --dry-run para aplicar los cambios');
        }

        return Command::SUCCESS;
    }

    private function processBrands(?string $idFilter): void
    {
        $query = Brand::query();

        if ($idFilter) {
            $query->where('id', $idFilter);
        }

        // Filtrar solo los que tienen webp en logo o banner
        $query->where(function ($q) {
            $q->where('logo', 'like', '%.webp')
                ->orWhere('banner', 'like', '%.webp');
        });

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->line("   No se encontraron registros con imágenes webp");
            return;
        }

        $this->line("   Encontrados: {$items->count()} registros con webp");
        $this->newLine();

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            $this->processItem($item, 'brands', ['logo', 'banner']);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function processModel(string $modelClass, string $table, array $fields, ?string $idFilter): void
    {
        $query = $modelClass::withoutGlobalScopes();

        if ($idFilter) {
            $query->where('id', $idFilter);
        }

        // Filtrar solo los que tienen webp en alguno de los campos
        $query->where(function ($q) use ($fields) {
            foreach ($fields as $index => $field) {
                if ($index === 0) {
                    $q->where($field, 'like', '%.webp');
                } else {
                    $q->orWhere($field, 'like', '%.webp');
                }
            }
        });

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->line("   No se encontraron registros con imágenes webp");
            return;
        }

        $this->line("   Encontrados: {$items->count()} registros con webp");
        $this->newLine();

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            $this->processItem($item, $table, $fields);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function processItem($item, string $table, array $fields): void
    {
        $updates = [];

        foreach ($fields as $field) {
            if ($item->{$field} && Str::endsWith($item->{$field}, '.webp')) {
                $result = $this->convertImage($item->{$field}, $field, $item->id, $table);
                if ($result) {
                    $updates[$field] = $result;
                }
            }
        }

        // Actualizar BD si hay cambios
        if (!empty($updates) && !$this->dryRun) {
            DB::table($table)->where('id', $item->id)->update($updates);
        }
    }

    private function convertImage(string $webpPath, string $field, int $itemId, string $table): ?string
    {
        $disk = Storage::disk('public');

        // Verificar que el archivo existe
        if (!$disk->exists($webpPath)) {
            $this->newLine();
            $this->warn("   ⚠️  [{$table}:{$itemId}] {$field}: Archivo no encontrado: {$webpPath}");
            $this->skipped++;
            return null;
        }

        // Generar nueva ruta con extensión .png
        $pngPath = preg_replace('/\.webp$/i', '.png', $webpPath);

        // Verificar si ya existe el PNG
        if ($disk->exists($pngPath)) {
            $this->newLine();
            $this->line("   ⏭️  [{$table}:{$itemId}] {$field}: PNG ya existe, actualizando referencia");
            $this->skipped++;

            // Aunque el PNG exista, devolver la ruta para actualizar la BD
            return $pngPath;
        }

        if ($this->dryRun) {
            $this->newLine();
            $this->line("   🔄 [{$table}:{$itemId}] {$field}: {$webpPath} → {$pngPath}");
            $this->converted++;
            return $pngPath;
        }

        try {
            // Leer imagen webp
            $fullPath = $disk->path($webpPath);
            $img = Image::read($fullPath);

            // Guardar como PNG
            $pngContent = $img->encode(new PngEncoder());
            $disk->put($pngPath, $pngContent);

            // Eliminar webp original si no se quiere mantener
            if (!$this->keepWebp) {
                $disk->delete($webpPath);
            }

            $this->newLine();
            $this->info("   ✅ [{$table}:{$itemId}] {$field}: Convertido a PNG");
            $this->converted++;

            return $pngPath;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("   ❌ [{$table}:{$itemId}] {$field}: Error - {$e->getMessage()}");
            $this->errors++;
            return null;
        }
    }
}
