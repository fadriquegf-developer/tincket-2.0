<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailerService
{
    protected array $mailerConfigs = [];

    public function getMailerForBrand(Brand $brand): Mailer
    {
        $configKey = "brand-{$brand->id}";

        // Si ya tenemos la configuración cacheada para esta brand
        if (!isset($this->mailerConfigs[$configKey])) {
            $this->configureMailerForBrand($brand, $configKey);
        }

        return Mail::mailer($configKey);
    }

    protected function configureMailerForBrand(Brand $brand, string $configKey): void
    {
        // Cargar configuración base
        $baseConfig = config('mail.mailers.smtp');

        // Cargar configuración específica de la brand
        $brandConfig = $this->getBrandMailConfig($brand);

        // Fusionar configuraciones
        $finalConfig = array_merge($baseConfig, $brandConfig);

        // Registrar el mailer dinámicamente
        config(["mail.mailers.{$configKey}" => $finalConfig]);

        // Cachear para esta ejecución
        $this->mailerConfigs[$configKey] = true;
    }

    protected function getBrandMailConfig(Brand $brand): array
    {
        // Cargar contexto de la brand temporalmente
        $originalBrand = get_current_brand();

        // Cambiar a la brand objetivo
        app(\App\Http\Middleware\CheckBrandHost::class)
            ->loadBrandConfig($brand->code_name);

        // Obtener configuración de mail de la brand
        $config = brand_setting('mail', []);

        // Restaurar brand original si existía
        if ($originalBrand) {
            app(\App\Http\Middleware\CheckBrandHost::class)
                ->loadBrandConfig($originalBrand->code_name);
        }

        return [
            'host' => $config['mailers']['smtp']['host'] ?? env('MAIL_HOST'),
            'port' => $config['mailers']['smtp']['port'] ?? env('MAIL_PORT'),
            'username' => $config['mailers']['smtp']['username'] ?? env('MAIL_USERNAME'),
            'password' => $config['mailers']['smtp']['password'] ?? env('MAIL_PASSWORD'),
            'encryption' => $config['mailers']['smtp']['encryption'] ?? env('MAIL_ENCRYPTION'),
            'from' => $config['from'] ?? [
                'address' => env('MAIL_FROM_ADDRESS'),
                'name' => env('MAIL_FROM_NAME')
            ],
        ];
    }
}
