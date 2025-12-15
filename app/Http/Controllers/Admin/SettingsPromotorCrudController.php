<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Traits\AllowUsersTrait;
use App\Uploaders\PngImageUploader;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SettingsPromotorCrudController extends CrudController
{
    use AllowUsersTrait;

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;


    public function setup()
    {
        CRUD::setModel(Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/custom-settings/promotor');
        CRUD::setEntityNameStrings(__('menu.setting_promotor'), __('menu.setting_promotor'));
    }

    /**
     * Sobreescribimos el index() para redirigir directamente al edit de la Brand actual.
     */
    public function index()
    {
        $brand = get_current_brand();
        if ($brand) {
            $editUrl = backpack_url("custom-settings/promotor/{$brand->id}/edit");
            return redirect($editUrl);
        }
        abort(404, 'Brand no encontrada');
    }

    /**
     * Configuramos la operación de actualización con los campos adicionales.
     */
    protected function setupUpdateOperation()
    {
        $this->setupBrandTab();
    }

    private function setupBrandTab()
    {

        $brand = get_current_brand();

        CRUD::field('logo')
            ->type('image')
            ->label(__('backend.brand_settings.logo'))
            ->crop(true)->aspect_ratio(2.5 / 1)
            ->withFiles([
                'disk' => 'public',
                'path' => "uploads/{$brand->code_name}/media",
                'uploader' => PngImageUploader::class,
                'fileNamer' => fn($file, $u) => 'logo-' . $u->entry->code_name . '.png',
                'resize' => ['max' => 300],
            ])
            ->wrapper(['class' => 'form-group col-md-6']);
    }
}
