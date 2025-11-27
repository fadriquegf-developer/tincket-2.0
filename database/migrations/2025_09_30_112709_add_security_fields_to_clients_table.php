<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecurityFieldsToClientsTable extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Campos de seguridad
            $table->json('password_history')->nullable()->after('password');
            $table->integer('failed_login_attempts')->default(0)->after('password_history');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_password_change')->nullable()->after('locked_until');
            $table->timestamp('password_expires_at')->nullable()->after('last_password_change');
            $table->timestamp('last_login_at')->nullable()->after('password_expires_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // Índices para mejorar performance
            $table->index('locked_until');
            $table->index('password_expires_at');
            $table->index(['email', 'failed_login_attempts']);
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'password_history',
                'failed_login_attempts',
                'locked_until',
                'last_password_change',
                'password_expires_at',
                'last_login_at',
                'last_login_ip'
            ]);
        });
    }
}
