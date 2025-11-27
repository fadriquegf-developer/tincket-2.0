<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\RedisSlotsService;
use App\Models\Session;

class FixSoldSlots extends Command
{
    protected $signature = 'slots:fix-sold {--session= : ID de sesión específica (opcional)} {--dry-run : Simular sin hacer cambios}';
    protected $description = 'Actualiza session_slot para marcar butacas vendidas como status_id=2';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $sessionId = $this->option('session');
        
        $this->info($dryRun ? '🔍 MODO SIMULACIÓN (no se harán cambios)' : '⚠️  MODO REAL (se harán cambios)');
        
        // Query para obtener los datos
        $query = DB::table('inscriptions as i')
            ->join('carts as c', 'c.id', '=', 'i.cart_id')
            ->whereNotNull('i.slot_id')
            ->whereNotNull('c.confirmation_code')
            ->whereNull('i.deleted_at')
            ->select('i.session_id', 'i.slot_id');
        
        if ($sessionId) {
            $query->where('i.session_id', $sessionId);
            $this->info("📍 Filtrando por sesión: {$sessionId}");
        }
        
        $inscriptions = $query->get();
        
        $this->info("📊 Encontradas {$inscriptions->count()} butacas vendidas");
        
        if ($inscriptions->isEmpty()) {
            $this->warn('No hay butacas para actualizar');
            return 0;
        }
        
        // Agrupar por sesión
        $bySession = $inscriptions->groupBy('session_id');
        
        $this->info("🎯 Afecta a " . $bySession->count() . " sesiones");
        
        if ($dryRun) {
            // Mostrar tabla de lo que se haría
            $table = [];
            foreach ($bySession as $sessId => $slots) {
                $table[] = [
                    'Sesión' => $sessId,
                    'Butacas' => $slots->count(),
                    'IDs' => $slots->pluck('slot_id')->take(5)->implode(', ') . '...'
                ];
            }
            $this->table(['Sesión', 'Butacas', 'IDs (muestra)'], $table);
            
            $this->warn('⚠️  Ejecuta sin --dry-run para aplicar cambios');
            return 0;
        }
        
        // Confirmar antes de continuar
        if (!$this->confirm('¿Continuar con la actualización?')) {
            $this->warn('❌ Cancelado');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($inscriptions->count());
        $bar->start();
        
        $updated = 0;
        $errors = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($inscriptions as $inscription) {
                try {
                    \App\Models\SessionSlot::updateOrCreate(
                        [
                            'session_id' => $inscription->session_id,
                            'slot_id' => $inscription->slot_id
                        ],
                        [
                            'status_id' => 2,
                            'comment' => null,
                            'updated_at' => now()
                        ]
                    );
                    $updated++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\nError en slot {$inscription->slot_id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            
            $this->newLine(2);
            $this->info("✅ Actualizados: {$updated}");
            if ($errors > 0) {
                $this->error("❌ Errores: {$errors}");
            }
            
            // Limpiar cache de Redis
            if ($this->confirm('¿Limpiar cache de Redis?', true)) {
                $this->info('🧹 Limpiando cache...');
                
                foreach ($bySession->keys() as $sessId) {
                    $session = Session::find($sessId);
                    if ($session) {
                        $redis = new RedisSlotsService($session);
                        $redis->clearAllCache();
                        $redis->regenerateCache();
                    }
                }
                
                $this->info('✅ Cache limpiado y regenerado');
            }
            
            $this->info('🎉 Proceso completado exitosamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error fatal: {$e->getMessage()}");
            return 1;
        }
        
        return 0;
    }
}