<?php

namespace App\Console\Commands;

use App\Models\Mailing;
use App\Models\Brand;
use Illuminate\Console\Command;

class MonitorMailings extends Command
{
    protected $signature = 'mailings:monitor {--mailing=} {--brand=}';
    protected $description = 'Monitorear el estado de los mailings por brand';

    public function handle(): void
    {
        $query = Mailing::with('batches', 'brand');

        // Filtrar por brand si se especifica
        if ($brandCode = $this->option('brand')) {
            $brand = Brand::where('code_name', $brandCode)->first();
            if ($brand) {
                $query->where('brand_id', $brand->id);
                $this->info("Filtrando por brand: {$brand->name}");
            }
        }

        if ($mailingId = $this->option('mailing')) {
            $query->where('id', $mailingId);
        } else {
            $query->where('status', 'processing');
        }

        $mailings = $query->get();

        if ($mailings->isEmpty()) {
            $this->info('No hay mailings en procesamiento');
            return;
        }

        foreach ($mailings as $mailing) {
            $this->info("Mailing #{$mailing->id}: {$mailing->name}");
            $this->info("Brand: {$mailing->brand->name} ({$mailing->brand->code_name})");
            $this->table(
                ['Estado', 'Total', 'Enviados', 'Fallidos', 'Progreso'],
                [[
                    $mailing->status,
                    $mailing->total_recipients,
                    $mailing->batches_sent,
                    $mailing->batches_failed,
                    $this->getProgressBar($mailing)
                ]]
            );

            if ($mailing->batches->isNotEmpty()) {
                $this->info('Detalle de batches:');
                $this->table(
                    ['Batch', 'Estado', 'Destinatarios', 'Enviado'],
                    $mailing->batches->map(fn($b) => [
                        $b->batch_number,
                        $b->status,
                        count($b->recipients),
                        $b->sent_at?->diffForHumans() ?? '-'
                    ])
                );
            }

            $this->newLine();
        }
    }

    private function getProgressBar(Mailing $mailing): string
    {
        $total = $mailing->batches->count();
        $sent = $mailing->batches_sent;

        if ($total === 0) return '0%';

        $percentage = round(($sent / $total) * 100);
        $filled = floor($percentage / 5);
        $empty = 20 - $filled;

        return str_repeat('█', $filled) . str_repeat('░', $empty) . " {$percentage}%";
    }
}
