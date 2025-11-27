<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla SIN foreign key - solo con índice
        Schema::create('mailing_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('mailing_id')->index();
            $table->unsignedInteger('brand_id')->index();
            $table->integer('batch_number');
            $table->json('recipients');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices compuestos importantes para multi-tenancy
            $table->index(['brand_id', 'mailing_id']);
            $table->index(['brand_id', 'status']);
            $table->index(['mailing_id', 'status']);
            $table->index('batch_number');
        });

        // Actualizar tabla mailings
        Schema::table('mailings', function (Blueprint $table) {
            // Solo intentar agregar columnas que no existen
            if (!Schema::hasColumn('mailings', 'status')) {
                $table->enum('status', ['draft', 'processing', 'sent', 'partial', 'failed'])->default('draft')->after('emails');
            }
            if (!Schema::hasColumn('mailings', 'total_recipients')) {
                $table->integer('total_recipients')->nullable()->after('status');
            }
            if (!Schema::hasColumn('mailings', 'batches_sent')) {
                $table->integer('batches_sent')->default(0)->after('total_recipients');
            }
            if (!Schema::hasColumn('mailings', 'batches_failed')) {
                $table->integer('batches_failed')->default(0)->after('batches_sent');
            }
            if (!Schema::hasColumn('mailings', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable();
            }
            if (!Schema::hasColumn('mailings', 'processing_completed_at')) {
                $table->timestamp('processing_completed_at')->nullable();
            }
            if (!Schema::hasColumn('mailings', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
            if (!Schema::hasColumn('mailings', 'failed_at')) {
                $table->timestamp('failed_at')->nullable();
            }
            if (!Schema::hasColumn('mailings', 'error_message')) {
                $table->text('error_message')->nullable();
            }
        });

        // MIGRAR DATOS DE is_sent A status ANTES DE ELIMINAR LA COLUMNA
        if (Schema::hasColumn('mailings', 'is_sent')) {
            // Mailings enviados (is_sent = 1) -> status = 'sent'
            DB::table('mailings')
                ->where('is_sent', 1)
                ->update([
                    'status' => 'sent',
                    'sent_at' => DB::raw('COALESCE(sent_at, updated_at, created_at)'),
                    'processing_completed_at' => DB::raw('COALESCE(processing_completed_at, updated_at, created_at)'),
                ]);

            // Mailings no enviados (is_sent = 0 o NULL) -> status = 'draft'
            DB::table('mailings')
                ->where(function ($query) {
                    $query->where('is_sent', 0)
                        ->orWhereNull('is_sent');
                })
                ->whereNull('status')
                ->update([
                    'status' => 'draft'
                ]);

            // Si hay mailings con status NULL pero is_sent tiene valor, actualizar
            DB::table('mailings')
                ->whereNull('status')
                ->update([
                    'status' => 'draft'
                ]);
        }

        // Calcular total_recipients para los mailings existentes
        $mailings = DB::table('mailings')
            ->whereNull('total_recipients')
            ->whereNotNull('emails')
            ->where('emails', '!=', '')
            ->get();

        foreach ($mailings as $mailing) {
            $count = substr_count($mailing->emails, ',') + 1;
            DB::table('mailings')
                ->where('id', $mailing->id)
                ->update(['total_recipients' => $count]);
        }

        // ELIMINAR is_sent DESPUÉS DE MIGRAR LOS DATOS
        Schema::table('mailings', function (Blueprint $table) {
            if (Schema::hasColumn('mailings', 'is_sent')) {
                $table->dropColumn('is_sent');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_batches');

        Schema::table('mailings', function (Blueprint $table) {
            // Restaurar is_sent si se hace rollback
            if (!Schema::hasColumn('mailings', 'is_sent')) {
                $table->boolean('is_sent')->default(false)->after('emails');
            }

            $columnsToRemove = [];

            if (Schema::hasColumn('mailings', 'status')) {
                // Antes de eliminar status, restaurar is_sent basado en status
                DB::table('mailings')
                    ->where('status', 'sent')
                    ->update(['is_sent' => 1]);

                DB::table('mailings')
                    ->where('status', '!=', 'sent')
                    ->update(['is_sent' => 0]);

                $columnsToRemove[] = 'status';
            }

            if (Schema::hasColumn('mailings', 'total_recipients')) {
                $columnsToRemove[] = 'total_recipients';
            }
            if (Schema::hasColumn('mailings', 'batches_sent')) {
                $columnsToRemove[] = 'batches_sent';
            }
            if (Schema::hasColumn('mailings', 'batches_failed')) {
                $columnsToRemove[] = 'batches_failed';
            }
            if (Schema::hasColumn('mailings', 'processing_started_at')) {
                $columnsToRemove[] = 'processing_started_at';
            }
            if (Schema::hasColumn('mailings', 'processing_completed_at')) {
                $columnsToRemove[] = 'processing_completed_at';
            }
            if (Schema::hasColumn('mailings', 'sent_at')) {
                $columnsToRemove[] = 'sent_at';
            }
            if (Schema::hasColumn('mailings', 'failed_at')) {
                $columnsToRemove[] = 'failed_at';
            }
            if (Schema::hasColumn('mailings', 'error_message')) {
                $columnsToRemove[] = 'error_message';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};
