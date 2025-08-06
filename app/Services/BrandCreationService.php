<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Models\Brand;
use App\Models\Capability;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

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
        'clients.frontend.url' => 'https://yesweticket.com/',
        'laravellocalization.supportedLocales.ca.name' => 'Catalan',
        'laravellocalization.supportedLocales.ca.script' => 'Latn',
        'laravellocalization.supportedLocales.ca.native' => 'català',
        'laravellocalization.supportedLocales.ca.regional' => 'ca_ES',
        'laravellocalization.useAcceptLanguageHeader' => '0',
        'laravellocalization.hideDefaultLocaleInURL' => '1'
    ];


    public function create($attributes): Brand|null
    {
        $brand = null;

        // Generar key si no viene
        if (empty($attributes['key'])) {
            $attributes['key'] = Str::random(32);
        }

        DB::transaction(function () use ($attributes, &$brand) {
            $brand = new Brand($attributes);
            $brand->save();

            $this->enableTpv($brand);
            $this->setDefaultSettings($brand);
            $this->setApplication($brand);

            $brand->users()->attach(User::findOrFail(1));
        });

        return $brand;
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
            $brand->settings()->create(compact('key', 'value'));
        }
    }

    private function setApplication(Brand $brand)
    {
        $brand->applications()->create([
            'code_name' => 'Web API',
            'key' => Str::random(32)
        ]);
    }

    private function enableTpv(Brand $brand)
    {
        if ($this->javajan_tpv) {
            $public_url = $brand->allowed_host;

            $tpv = $brand->tpvs()->create([
                'name' => 'TPV Javajan',
                'omnipay_type' => 'Sermepa',
                'config' => str_replace(
                    [':public_url:', ':brand_name:'],
                    [trim($public_url, '/'), $brand->name],
                    collect(config('services.javajan.sermepa', []))
                        ->map(fn($value, $key) => ['key' => $key, 'value' => $value])
                        ->values()
                        ->toJson()
                ),
            ]);

            $brand->extra_config = [
                'cartTTL' => Cart::DEFAULT_MINUTES_TO_EXPIRE,
                'maxCartTTL' => Cart::DEFAULT_MINUTES_TO_COMPLETE,
                'default_tpv_id' => $tpv->id,
            ];

            $brand->save();
        }
    }
}
