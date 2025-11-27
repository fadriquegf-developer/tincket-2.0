<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportDatabaseStructure extends Command
{
    protected $signature = 'db:export-structure {--output=database-structure.txt}';
    protected $description = 'Exporta la estructura completa de la base de datos';

    public function handle()
    {
        $output = '';
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            $output .= "\n=== TABLA: {$tableName} ===\n\n";

            $columns = DB::select("DESCRIBE {$tableName}");

            foreach ($columns as $column) {
                $output .= sprintf(
                    "Campo: %-30s Tipo: %-20s Null: %-3s Key: %-4s Default: %s Extra: %s\n",
                    $column->Field,
                    $column->Type,
                    $column->Null,
                    $column->Key,
                    $column->Default ?? 'NULL',
                    $column->Extra
                );
            }

            $output .= "\n" . str_repeat('-', 100) . "\n";
        }

        file_put_contents($this->option('output'), $output);
        $this->info("Estructura exportada a: {$this->option('output')}");
    }
}
