<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Models\Brand;
use App\Models\Capability;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BrandCreationException;

class BrandCreationService
{
    private $javajan_tpv = false;
    private $default_settings = [
        'app.locale' => 'ca',
        'backpack.base.project_name' => 'YesWeTicket',
        'backpack.base.logo_lg' => 'YesWe<b>Ticket</b>',
        'backpack.base.logo_mini' => 'YW<b>T</b>',
        'backpack.crud.default_page_length' => '25',
        'backpack.crud.show_translatable_field_icon' => '',
        'backpack.crud.translatable_field_icon_position' => 'right',
        'backpack.crud.locales.ca' => 'Catalan',
        'clients.frontend.url' => 'https://ticketara.com/',
        'laravellocalization.supportedLocales.ca.name' => 'Catalan',
        'laravellocalization.supportedLocales.ca.script' => 'Latn',
        'laravellocalization.supportedLocales.ca.native' => 'catala',
        'laravellocalization.supportedLocales.ca.regional' => 'ca_ES',
        'laravellocalization.useAcceptLanguageHeader' => '0',
        'laravellocalization.hideDefaultLocaleInURL' => '1'
    ];

    /**
     * Crea una nueva Brand con todas sus configuraciones
     * 
     * @param array $attributes
     * @return Brand|null
     * @throws BrandCreationException
     */
    public function create($attributes): Brand|null
    {
        $brand = null;

        // Generar key si no viene
        if (empty($attributes['key'])) {
            $attributes['key'] = $this->generateSecureKey();
        }

        // Sanitizar allowed_host si existe
        if (!empty($attributes['allowed_host'])) {
            $attributes['allowed_host'] = $this->sanitizeAndValidateDomain($attributes['allowed_host']);
        }

        try {
            DB::transaction(function () use ($attributes, &$brand) {
                // Crear la marca
                $brand = new Brand($attributes);
                $brand->save();

                // Configurar TPV si está habilitado
                if ($this->javajan_tpv) {
                    $this->enableTpv($brand);
                }

                // Establecer configuraciones por defecto
                $this->setDefaultSettings($brand);

                // Crear aplicación asociada
                $this->setApplication($brand);

                // Asignar usuario superadmin
                $this->assignSuperAdmin($brand);
            });

            return $brand;
        } catch (\Exception $e) {
            Log::error('Failed to create brand', [
                'error' => $e->getMessage(),
                'attributes' => $attributes
            ]);

            throw new BrandCreationException('Error creating brand: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Genera una clave API segura
     */
    private function generateSecureKey(): string
    {
        return bin2hex(random_bytes(16)); // 32 caracteres hexadecimales
    }

    /**
     * Sanitiza y valida un dominio
     * 
     * @param string $domain
     * @return string
     * @throws BrandCreationException
     */
    private function sanitizeAndValidateDomain($domain): string
    {
        // Eliminar espacios
        $domain = trim($domain);

        // Eliminar protocolo si existe
        $domain = preg_replace('/^https?:\/\//', '', $domain);

        // Eliminar www.
        $domain = preg_replace('/^www\./', '', $domain);

        // Eliminar path y query strings
        $domain = explode('/', $domain)[0];
        $domain = explode('?', $domain)[0];

        // Convertir a minúsculas
        $domain = strtolower($domain);

        // Validar formato de dominio
        if (!preg_match('/^([\da-z\.-]+\.)+[a-z]{2,}$/i', $domain)) {
            throw new BrandCreationException('Invalid domain format: ' . $domain);
        }

        // Validar que no sea localhost o dominio local
        if ($this->isLocalDomain($domain)) {
            throw new BrandCreationException('Domain cannot be localhost or local domain: ' . $domain);
        }

        return $domain;
    }

    /**
     * Verifica si un dominio es local
     */
    private function isLocalDomain($domain): bool
    {
        $localDomains = [
            'localhost',
            'local',
            'test',
            'example.com',
            'example.org',
            'example.net'
        ];

        // Verificar dominios locales exactos
        if (in_array(strtolower($domain), $localDomains)) {
            return true;
        }

        // Verificar TLDs locales comunes
        if (preg_match('/\.(local|localhost|test|invalid)$/i', $domain)) {
            return true;
        }

        // Verificar si es una IP
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            // Verificar si es IP privada o localhost
            return !filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }

    public function setSetting($key, $value)
    {
        $this->default_settings[$key] = $value;
        return $this;
    }

    public function withJavajanTpv()
    {
        $this->javajan_tpv = true;
        return $this;
    }

    public function withoutJavajanTpv()
    {
        $this->javajan_tpv = false;
        return $this;
    }

    private function setDefaultSettings(Brand $brand)
    {
        foreach ($this->default_settings as $key => $value) {
            $brand->settings()->create([
                'key' => $key,
                'value' => $value,
                'brand_id' => $brand->id
            ]);
        }
    }

    private function setApplication(Brand $brand)
    {
        $brand->applications()->create([
            'code_name' => 'Web API',
            'key' => $this->generateSecureKey()
        ]);
    }

    private function assignSuperAdmin(Brand $brand)
    {
        try {
            $superAdmin = User::findOrFail(1);
            $brand->users()->attach($superAdmin->id);
        } catch (\Exception $e) {
            Log::warning('Could not assign super admin to brand', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function enableTpv(Brand $brand)
    {
        try {
            $public_url = $brand->allowed_host;

            if (empty($public_url)) {
                Log::warning('TPV enabled but no allowed_host set for brand', ['brand_id' => $brand->id]);
                return;
            }

            // Sanitizar URL antes de usarla en la configuración
            $safe_domain = $this->sanitizeAndValidateDomain($public_url);
            $safe_url = 'https://' . $safe_domain;
            $safe_brand_name = htmlspecialchars($brand->name, ENT_QUOTES, 'UTF-8');

            // Crear el TPV con los nuevos campos
            $tpv = $brand->tpvs()->create([
                'name' => 'TPV Javajan',
                'omnipay_type' => 'Sermepa',
                'config' => $this->generateTpvConfig($safe_url, $safe_brand_name),
                // Nuevos campos importantes
                'is_active' => true,
                'is_default' => true,
                'is_test_mode' => !app()->environment('production'),
                'priority' => 100
            ]);

            // Configurar extra_config de la Brand (sin el default_tpv_id ya no es necesario)
            $brand->extra_config = [
                'cartTTL' => Cart::DEFAULT_MINUTES_TO_EXPIRE,
                'maxCartTTL' => Cart::DEFAULT_MINUTES_TO_COMPLETE,
            ];

            $brand->save();
        } catch (\Exception $e) {
            Log::error('Failed to enable TPV for brand', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage()
            ]);
            throw new BrandCreationException('Failed to configure TPV: ' . $e->getMessage());
        }
    }

    /**
     * Genera la configuración del TPV de forma segura
     */
    private function generateTpvConfig($url, $brandName): array  // ← Cambiar return type
    {
        $config = config('services.javajan.sermepa', []);

        $processedConfig = collect($config)
            ->map(function ($value, $key) use ($url, $brandName) {
                if (is_string($value)) {
                    $value = str_replace(':public_url:', $url, $value);
                    $value = str_replace(':brand_name:', $brandName, $value);
                }
                return ['key' => $key, 'value' => $value];
            })
            ->values()
            ->toArray();

        return $processedConfig;
    }
}
